<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionNegocio;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Sucursal;
use App\Models\User;
use App\Rules\RucEcuatoriano;
use App\Services\ContextoNegocio;
use App\Services\GuardiaEliminacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarDatos($request);

        $claveGenerada = $datos['clave_admin'] ?? Str::password(14);

        $negocio = DB::transaction(function () use ($request, $datos, $claveGenerada) {
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

            return $negocio;
        });

        return redirect()->route('plataforma.negocios.show', $negocio)
            ->with('credenciales', [
                'correo' => $datos['correo_admin'],
                'clave' => $claveGenerada,
                'nombre' => $datos['nombre_admin'],
            ])
            ->with('success', "Bar {$negocio->nombre} creado con propietario.");
    }

    public function show(Negocio $negocio): View
    {
        $negocio->load([
            'sucursales' => fn ($q) => $q->withoutGlobalScope('negocio'),
            'contratos.pagos',
        ]);

        return view('plataforma.negocios.show', [
            'negocio' => $negocio,
            'propietario' => MembresiaNegocio::with('usuario')
                ->where('negocio_id', $negocio->id)
                ->where('rol', 'propietario')
                ->first(),
        ]);
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
            ConfiguracionNegocio::withoutGlobalScope('negocio')->where('negocio_id', $negocio->id)->delete();
            MembresiaNegocio::where('negocio_id', $negocio->id)->update(['esta_activa' => false]);
            $negocio->contratos()->whereIn('estado', ['pendiente', 'activo'])->update(['estado' => 'cancelado']);
            $negocio->delete();
        });

        return redirect()->route('plataforma.negocios.index')->with('success', 'Bar eliminado.');
    }

    public function desactivar(Negocio $negocio): RedirectResponse
    {
        $negocio->esta_activo = false;
        $negocio->save();

        return redirect()->route('plataforma.negocios.index')->with('success', 'Bar desactivado por tener ventas registradas.');
    }

    private function validarDatos(Request $request, ?Negocio $negocio = null): array
    {
        $reglas = [
            'nombre' => 'required|string|max:255',
            'ruc' => ['nullable', 'string', 'size:13', new RucEcuatoriano(), Rule::unique('negocios', 'ruc')->ignore($negocio?->id)->whereNull('deleted_at')],
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
