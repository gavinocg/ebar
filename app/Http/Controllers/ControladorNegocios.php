<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionNegocio;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Sucursal;
use App\Models\User;
use App\Mail\CredencialesPrimerIngreso;
use App\Rules\RucEcuatoriano;
use App\Services\ContextoNegocio;
use App\Services\GuardiaEliminacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorNegocios extends Controller
{
    public function index(): View
    {
        $negocios = Negocio::with([
            'sucursales' => fn ($q) => $q->withoutGlobalScope('negocio'),
            'contratos' => fn ($q) => $q->withoutGlobalScope('negocio')
                ->where('estado', 'activo')
                ->whereDate('fecha_fin', '>=', now()->toDateString())
                ->orderByDesc('fecha_fin'),
        ])->orderBy('nombre')->get();

        return view('plataforma.negocios.index', compact('negocios'));
    }

    public function create(): View
    {
        return view('plataforma.negocios.create', [
            'zonasHorarias' => $this->zonasHorarias(),
            'monedas' => ['USD' => 'USD - Dólar estadounidense'],
        ]);
    }

    public function reactivar(string $ruc): RedirectResponse
    {
        $negocio = Negocio::withTrashed()->where('ruc', $ruc)->whereNotNull('deleted_at')->first();

        abort_unless($negocio, 404, 'No se encontró un bar inactivado con ese RUC.');

        $negocio->restore();
        $negocio->esta_activo = true;
        $negocio->save();

        MembresiaNegocio::where('negocio_id', $negocio->id)->update(['esta_activa' => true]);

        return redirect()->route('plataforma.negocios.index')
            ->with('success', "Bar {$negocio->nombre} reactivado. Se recuperó su información.");
    }

    public function autocompletarCedula(string $cedula): JsonResponse
    {
        $usuario = User::where('cedula', $cedula)->first();

        if (!$usuario) {
            return response()->json(['encontrado' => false]);
        }

        return response()->json([
            'encontrado' => true,
            'nombre' => $usuario->nombre,
            'correo' => $usuario->correo,
            'celular' => $usuario->celular,
        ]);
    }

    public function autocompletarRuc(string $ruc): JsonResponse
    {
        $negocio = Negocio::withTrashed()->where('ruc', $ruc)->first();

        if (!$negocio) {
            return response()->json(['encontrado' => false]);
        }

        $sucursal = Sucursal::withoutGlobalScope('negocio')
            ->where('negocio_id', $negocio->id)
            ->orderBy('id')
            ->first();

        return response()->json([
            'encontrado' => true,
            'nombre' => $negocio->nombre,
            'zona_horaria' => $negocio->zona_horaria,
            'moneda' => $negocio->moneda,
            'nombre_sucursal' => $sucursal?->nombre,
            'eliminado' => $negocio->trashed(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarDatos($request);

        $claveGenerada = $datos['clave_admin'] ?? Str::password(14);

        [$negocio, $admin] = DB::transaction(function () use ($request, $datos, $claveGenerada) {
            $negocio = Negocio::create([
                'nombre' => $datos['nombre'],
                'identificador' => $this->generarIdentificador($datos['nombre']),
                'ruc' => $datos['ruc'] ?? null,
                'logo' => $this->guardarLogo($request),
                'esta_activo' => true,
                'zona_horaria' => $datos['zona_horaria'] ?? 'America/Guayaquil',
                'moneda' => $datos['moneda'] ?? 'USD',
            ]);

            app(ContextoNegocio::class)->establecer($negocio->id);

            Sucursal::create([
                'nombre' => $datos['nombre_sucursal'] ?? 'Sucursal principal',
                'esta_activa' => true,
            ]);

            $configuracion = new ConfiguracionNegocio(['nombre_negocio' => $negocio->nombre]);
            $configuracion->negocio_id = $negocio->id;
            $configuracion->save();

            $admin = User::where('correo', $datos['correo_admin'])->first();

            if ($admin) {
                $admin->nombre = $datos['nombre_admin'];
                $admin->cedula = $datos['cedula_admin'] ?? null;
                $admin->celular = $datos['celular_admin'] ?? null;
                $admin->password = $claveGenerada;
                $admin->debe_cambiar_password = true;
            } else {
                $admin = new User();
                $admin->nombre = $datos['nombre_admin'];
                $admin->correo = $datos['correo_admin'];
                $admin->cedula = $datos['cedula_admin'] ?? null;
                $admin->celular = $datos['celular_admin'] ?? null;
                $admin->password = $claveGenerada;
                $admin->debe_cambiar_password = true;
            }
            $admin->save();

            MembresiaNegocio::create([
                'negocio_id' => $negocio->id,
                'usuario_id' => $admin->id,
                'rol' => 'propietario',
                'esta_activa' => true,
            ]);

            return [$negocio, $admin];
        });

        try {
            Mail::to($admin->correo)->send(new CredencialesPrimerIngreso(
                nombre: $admin->nombre,
                correo: $admin->correo,
                clave: $claveGenerada,
                nombreBar: $negocio->nombre,
                url: route('inicio_sesion'),
            ));
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar las credenciales al propietario del bar ' . $negocio->id . ': ' . $e->getMessage());
        }

        return redirect()->route('plataforma.negocios.index')
            ->with('success', "Bar {$negocio->nombre} creado. Se enviaron las credenciales de primer ingreso al correo {$admin->correo}.");
    }

    public function edit(Negocio $negocio): View
    {
        $negocio->load([
            'contratos.pagos',
            'sucursales' => fn ($q) => $q->withoutGlobalScope('negocio'),
        ]);

        return view('plataforma.negocios.edit', [
            'negocio' => $negocio,
            'propietario' => MembresiaNegocio::with('usuario')
                ->where('negocio_id', $negocio->id)
                ->where('rol', 'propietario')
                ->first(),
            'zonasHorarias' => $this->zonasHorarias(),
            'monedas' => ['USD' => 'USD - Dólar estadounidense'],
        ]);
    }

    public function actualizarPropietario(Request $request, Negocio $negocio): RedirectResponse
    {
        $membresia = MembresiaNegocio::where('negocio_id', $negocio->id)
            ->where('rol', 'propietario')
            ->first();

        abort_unless($membresia?->usuario !== null, 404, 'Este bar no tiene propietario registrado.');

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => ['required', 'email', Rule::unique('usuarios', 'correo')->ignore($membresia->usuario_id)],
            'cedula' => ['nullable', 'string', 'size:10', new \App\Rules\CedulaEcuatoriana()],
            'celular' => 'nullable|string|max:20',
            'esta_activo' => 'nullable|boolean',
            'clave' => 'nullable|string|min:8|confirmed',
        ]);

        $usuario = $membresia->usuario;
        $usuario->nombre = $datos['nombre'];
        $usuario->correo = $datos['correo'];
        $usuario->cedula = $datos['cedula'] ?? null;
        $usuario->celular = $datos['celular'] ?? null;
        $usuario->esta_activo = $request->boolean('esta_activo');

        if (!empty($datos['clave'])) {
            $usuario->password = $datos['clave'];
            $usuario->debe_cambiar_password = true;
        }

        $usuario->save();

        $mensaje = empty($datos['clave'])
            ? 'Propietario actualizado.'
            : 'Propietario actualizado. El propietario deberá cambiar la contraseña en su próximo ingreso.';

        return redirect()->route('plataforma.negocios.edit', $negocio)->with('success', $mensaje);
    }

    public function storeSucursal(Request $request, Negocio $negocio): RedirectResponse
    {
        $datos = $this->validarSucursal($request);

        $sucursal = new Sucursal(array_merge($datos, ['esta_activa' => true]));
        $sucursal->negocio_id = $negocio->id;
        $sucursal->save();

        return redirect()->route('plataforma.negocios.edit', $negocio)->with('success', 'Sucursal creada.');
    }

    public function updateSucursal(Request $request, int $sucursal): RedirectResponse
    {
        $sucursal = Sucursal::withoutGlobalScope('negocio')->findOrFail($sucursal);
        $datos = $this->validarSucursal($request);

        $sucursal->fill($datos);
        $sucursal->esta_activa = $request->boolean('esta_activa');
        $sucursal->save();

        return redirect()->route('plataforma.negocios.edit', $sucursal->negocio_id)->with('success', 'Sucursal actualizada.');
    }

    public function destroySucursal(int $sucursal): RedirectResponse
    {
        $sucursal = Sucursal::withoutGlobalScope('negocio')->findOrFail($sucursal);

        $dependencias = GuardiaEliminacion::sucursalConDependencias($sucursal->id);

        if ($dependencias) {
            return back()->with('no_eliminable', [
                'entidad' => 'sucursal',
                'dependencias' => array_values(array_unique($dependencias)),
                'url' => route('plataforma.sucursales.desactivar', $sucursal->id),
            ]);
        }

        $negocioId = $sucursal->negocio_id;
        $sucursal->delete();

        return redirect()->route('plataforma.negocios.edit', $negocioId)->with('success', 'Sucursal eliminada.');
    }

    public function desactivarSucursal(int $sucursal): RedirectResponse
    {
        $sucursal = Sucursal::withoutGlobalScope('negocio')->findOrFail($sucursal);
        $negocioId = $sucursal->negocio_id;

        $sucursal->esta_activa = false;
        $sucursal->save();

        return redirect()->route('plataforma.negocios.edit', $negocioId)->with('success', 'Sucursal desactivada.');
    }

    public function update(Request $request, Negocio $negocio): RedirectResponse
    {
        $datos = $this->validarDatos($request, $negocio);

        $negocio->update([
            'nombre' => $datos['nombre'],
            'ruc' => $datos['ruc'] ?? $negocio->ruc,
            'logo' => $this->guardarLogo($request, $negocio->logo),
            'zona_horaria' => $datos['zona_horaria'] ?? $negocio->zona_horaria,
            'moneda' => $datos['moneda'] ?? $negocio->moneda,
            'esta_activo' => $request->boolean('esta_activo'),
        ]);

        return redirect()->route('plataforma.negocios.edit', $negocio)->with('success', 'Bar actualizado.');
    }

    public function destroy(Negocio $negocio): RedirectResponse
    {
        $dependencias = GuardiaEliminacion::negocioConDependencias($negocio->id);

        if ($dependencias) {
            return redirect()->route('plataforma.negocios.index')
                ->with('no_eliminable', [
                    'entidad' => 'bar',
                    'dependencias' => array_values(array_unique($dependencias)),
                    'url' => route('plataforma.negocios.desactivar', $negocio),
                ]);
        }

        DB::transaction(function () use ($negocio) {
            $this->eliminarFisico($negocio);
        });

        return redirect()->route('plataforma.negocios.index')->with('success', 'Bar eliminado físicamente con todos sus registros.');
    }

    /**
     * Elimina físicamente un bar sin actividad (sin ventas ni pagos registrados):
     * negocio, sucursales, contratos, catálogo, operación y usuarios del bar.
     */
    private function eliminarFisico(Negocio $negocio): void
    {
        $negocioId = $negocio->id;

        $usuarioIds = DB::table('membresias_negocio')->where('negocio_id', $negocioId)->pluck('usuario_id');

        $tablas = [
            'impresoras',
            'detalles_venta',
            'ventas',
            'reembolsos',
            'tickets_abiertos_detalles',
            'tickets_abiertos',
            'movimientos_efectivo',
            'movimientos_inventario',
            'conteos_inventario',
            'ordenes_compra',
            'turnos_cajero',
            'producto_variantes',
            'modificadores',
            'grupos_modificadores',
            'productos',
            'categorias',
            'clientes',
            'proveedores',
            'roles',
            'membresias_negocio',
            'configuraciones_negocio',
            'contratos',
            'auditorias',
            'sucursales',
        ];

        foreach ($tablas as $tabla) {
            DB::table($tabla)->where('negocio_id', $negocioId)->delete();
        }

        DB::table('negocios')->where('id', $negocioId)->delete();

        foreach ($usuarioIds as $usuarioId) {
            $usuario = DB::table('usuarios')->find($usuarioId);

            if (!$usuario || $usuario->rol === 'super_admin') {
                continue;
            }

            if (DB::table('membresias_negocio')->where('usuario_id', $usuarioId)->exists()) {
                continue;
            }

            DB::table('pin_intentos')->where('usuario_id', $usuarioId)->delete();
            DB::table('password_reset_tokens')->where('email', $usuario->correo)->delete();
            DB::table('usuarios')->where('id', $usuarioId)->delete();
        }
    }

    public function desactivar(Negocio $negocio): RedirectResponse
    {
        $negocio->esta_activo = false;
        $negocio->save();

        return redirect()->route('plataforma.negocios.index')->with('success', 'Bar desactivado por tener ventas o pagos registrados.');
    }

    private function validarDatos(Request $request, ?Negocio $negocio = null): array
    {
        $reglas = [
            'nombre' => 'required|string|max:255',
            'ruc' => [
                'nullable', 'string', 'size:13', new RucEcuatoriano(),
                Rule::unique('negocios', 'ruc')->ignore($negocio?->id)->whereNull('deleted_at'),
                function ($attribute, $value, $fail) use ($negocio) {
                    if ($value && $negocio === null && Negocio::withTrashed()->where('ruc', $value)->whereNotNull('deleted_at')->exists()) {
                        $fail('Este RUC pertenece a un bar inactivado. Puedes reactivarlo o ingresar un RUC distinto.');
                    }
                },
            ],
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'zona_horaria' => 'nullable|string|max:60',
            'moneda' => 'nullable|string|size:3',
            'nombre_sucursal' => 'nullable|string|max:255',
        ];

        if ($negocio === null) {
            $reglas = array_merge($reglas, [
                'nombre_admin' => 'required|string|max:255',
                'correo_admin' => ['required', 'email', function ($attribute, $value, $fail) {
                    $usuario = User::where('correo', $value)->first();

                    if (!$usuario) {
                        return;
                    }

                    if ($usuario->rol === 'super_admin') {
                        $fail('El campo :attribute ya ha sido registrado.');
                        return;
                    }

                    foreach ($usuario->membresias as $membresia) {
                        $negocio = Negocio::withTrashed()->find($membresia->negocio_id);

                        if ($negocio && !$negocio->trashed()) {
                            $fail('El campo :attribute ya ha sido registrado.');
                            return;
                        }
                    }
                }],
                'cedula_admin' => ['nullable', 'string', 'size:10', new \App\Rules\CedulaEcuatoriana()],
                'celular_admin' => 'nullable|string|max:20',
                'clave_admin' => 'nullable|string|min:8',
            ]);
        }

        return $request->validate($reglas);
    }

    private function validarSucursal(Request $request): array
    {
        return $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'provincia' => 'nullable|string|max:100',
            'canton' => 'nullable|string|max:100',
            'ciudad' => 'nullable|string|max:100',
        ]);
    }

    private function generarIdentificador(string $nombre): string
    {
        $base = Str::slug($nombre);

        $identificador = $base ?: 'bar';
        $i = 1;

        while (Negocio::withTrashed()->where('identificador', $identificador)->lockForUpdate()->exists()) {
            $identificador = $base ? $base . '-' . $i : 'bar-' . $i;
            $i++;
        }

        return $identificador;
    }

    private function guardarLogo(Request $request, ?string $actual = null): ?string
    {
        if (!$request->hasFile('logo')) {
            return $actual;
        }

        if ($actual && \Illuminate\Support\Facades\Storage::disk('public')->exists($actual)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($actual);
        }

        return $request->file('logo')->store('negocios', 'public');
    }

    private function zonasHorarias(): array
    {
        return ['America/Guayaquil' => 'America/Guayaquil'];
    }
}
