<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\ConfiguracionNegocio;
use App\Models\Contrato;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Plan;
use App\Models\Sucursal;
use App\Models\User;
use App\Rules\RucEcuatoriano;
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
            'sucursales',
            'membresia.plan',
            'contratos' => fn ($q) => $q->where('estado', 'activo')
                ->whereDate('fecha_fin', '>=', now()->toDateString())
                ->orderByDesc('fecha_fin'),
        ])->orderBy('nombre')->get();

        return view('plataforma.negocios.index', compact('negocios'));
    }

    public function create(): View
    {
        return view('plataforma.negocios.create', [
            'planes' => Plan::where('esta_activo', true)->orderBy('precio_mensual')->get(),
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
                'numero_sucursales_contratadas' => $datos['numero_sucursales_contratadas'],
            ]);

            app(\App\Services\ContextoNegocio::class)->establecer($negocio->id);

            Sucursal::create([
                'nombre' => $datos['nombre_sucursal'] ?? 'Sucursal principal',
                'esta_activa' => true,
                'n_cajeros_contratados' => $datos['n_cajeros_sucursal'] ?? 1,
            ]);

            $configuracion = new ConfiguracionNegocio(['nombre_negocio' => $negocio->nombre]);
            $configuracion->negocio_id = $negocio->id;
            $configuracion->save();

            $plan = Plan::findOrFail($datos['plan_id']);

            \App\Models\Membresia::create([
                'negocio_id' => $negocio->id,
                'plan_id' => $plan->id,
                'estado' => 'prueba',
                'fecha_inicio' => now(),
                'fecha_vencimiento' => now()->addDays(max((int) $plan->duracion_dias, 30)),
            ]);

            Contrato::create([
                'negocio_id' => $negocio->id,
                'fecha_inicio' => now(),
                'fecha_fin' => now()->addYear(),
                'forma_contratacion' => 'mensual',
                'estado' => 'activo',
            ]);

            $admin = new User();
            $admin->nombre = $datos['nombre_admin'];
            $admin->correo = $datos['correo_admin'];
            $admin->cedula = $datos['cedula_admin'] ?? null;
            $admin->celular = $datos['celular_admin'] ?? null;
            $admin->password = $claveGenerada;
            $admin->debe_cambiar_password = true;
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
        $negocio->load('membresia.plan', 'sucursales', 'contratos.pagos');

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
        return view('plataforma.negocios.edit', [
            'negocio' => $negocio,
            'planes' => Plan::where('esta_activo', true)->orderBy('precio_mensual')->get(),
            'zonasHorarias' => $this->zonasHorarias(),
            'monedas' => ['USD' => 'USD - Dólar estadounidense'],
        ]);
    }

    public function update(Request $request, Negocio $negocio): RedirectResponse
    {
        $datos = $this->validarDatos($request, $negocio);

        $planNuevo = Plan::findOrFail($datos['plan_id']);
        $problemas = $this->conflictosDeLimites($negocio, $planNuevo);

        if ($problemas) {
            return back()->withInput()->withErrors([
                'plan_id' => 'No se puede cambiar el plan: ' . implode(' ', $problemas),
            ]);
        }

        $negocio->update([
            'nombre' => $datos['nombre'],
            'ruc' => $datos['ruc'] ?? $negocio->ruc,
            'logo' => $this->guardarLogo($request, $negocio->logo),
            'zona_horaria' => $datos['zona_horaria'] ?? $negocio->zona_horaria,
            'moneda' => $datos['moneda'] ?? $negocio->moneda,
            'esta_activo' => $request->boolean('esta_activo'),
            'numero_sucursales_contratadas' => $datos['numero_sucursales_contratadas'],
        ]);

        $negocio->membresia()?->update(['plan_id' => $datos['plan_id']]);

        return redirect()->route('plataforma.negocios.show', $negocio)->with('success', 'Bar actualizado.');
    }

    public function destroy(Negocio $negocio): RedirectResponse
    {
        if ($negocio->ventas()->exists()) {
            return redirect()->route('plataforma.negocios.index')
                ->with('error', 'No se puede eliminar un bar que tiene ventas registradas.');
        }

        DB::transaction(function () use ($negocio) {
            $negocio->configuracion()->delete();
            MembresiaNegocio::where('negocio_id', $negocio->id)->update(['esta_activa' => false]);
            $negocio->contratos()->where('estado', 'activo')->update(['estado' => 'cancelado']);
            $negocio->delete();
        });

        return redirect()->route('plataforma.negocios.index')->with('success', 'Bar eliminado.');
    }

    private function validarDatos(Request $request, ?Negocio $negocio = null): array
    {
        $reglas = [
            'nombre' => 'required|string|max:255',
            'ruc' => ['nullable', 'string', 'size:13', new RucEcuatoriano(), Rule::unique('negocios', 'ruc')->ignore($negocio?->id)],
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'numero_sucursales_contratadas' => 'required|integer|min:1|max:100',
            'zona_horaria' => 'nullable|string|max:60',
            'moneda' => 'nullable|string|size:3',
            'plan_id' => ['required', Rule::exists('planes', 'id')->where('esta_activo', true)],
            'nombre_sucursal' => 'nullable|string|max:255',
            'n_cajeros_sucursal' => 'nullable|integer|min:0|max:50',
        ];

        if ($negocio === null) {
            $reglas = array_merge($reglas, [
                'nombre_admin' => 'required|string|max:255',
                'correo_admin' => ['required', 'email', 'unique:usuarios,correo'],
                'cedula_admin' => ['nullable', 'string', 'size:10', new \App\Rules\CedulaEcuatoriana()],
                'celular_admin' => 'nullable|string|max:20',
                'clave_admin' => 'nullable|string|min:8',
            ]);
        }

        return $request->validate($reglas);
    }

    private function conflictosDeLimites(Negocio $negocio, Plan $plan): array
    {
        $problemas = [];

        $sucursalesActivas = Sucursal::where('negocio_id', $negocio->id)->where('esta_activa', true)->count();
        if ((int) $plan->limite_sucursales > 0 && $sucursalesActivas > (int) $plan->limite_sucursales) {
            $problemas[] = "el bar tiene {$sucursalesActivas} sucursales activas y el plan permite {$plan->limite_sucursales}.";
        }

        $cajerosActivos = MembresiaNegocio::where('negocio_id', $negocio->id)
            ->where('rol', 'cajero')
            ->where('esta_activa', true)
            ->count();
        if ((int) $plan->limite_cajeros > 0 && $cajerosActivos > (int) $plan->limite_cajeros) {
            $problemas[] = "el bar tiene {$cajerosActivos} cajeros activos y el plan permite {$plan->limite_cajeros}.";
        }

        $cajasActivas = Caja::where('negocio_id', $negocio->id)->where('esta_activa', true)->count();
        if ((int) $plan->limite_cajas > 0 && $cajasActivas > (int) $plan->limite_cajas) {
            $problemas[] = "el bar tiene {$cajasActivas} cajas activas y el plan permite {$plan->limite_cajas}.";
        }

        return $problemas;
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