<?php

namespace App\Http\Controllers;

use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\TurnoCaja;
use App\Models\User;
use App\Services\ContextoNegocio;
use App\Services\RegistradorAuditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorCajeros extends Controller
{
    public function index(): View
    {
        $negocioId = app(ContextoNegocio::class)->id();
        $negocio = Negocio::with('membresia.plan')->findOrFail($negocioId);

        $cajeros = MembresiaNegocio::with('usuario', 'sucursal')
            ->where('negocio_id', $negocioId)
            ->where('rol', 'cajero')
            ->orderByDesc('esta_activa')
            ->get();

        $sucursales = Sucursal::where('negocio_id', $negocioId)->where('esta_activa', true)->orderBy('nombre')->get();

        $rolesPersonalizados = Rol::where('negocio_id', $negocioId)
            ->where('es_sistema', false)
            ->orderBy('nombre')
            ->get();

        $rolCajero = Rol::where('slug', 'cajero')
            ->where(fn ($q) => $q->where('negocio_id', $negocioId)->orWhereNull('negocio_id'))
            ->orderByDesc('negocio_id')
            ->first();

        $limite = $this->resolverLimiteCajeros($negocio);

        return view('cajeros.index', [
            'cajeros' => $cajeros,
            'limiteCajeros' => $limite,
            'limiteAlcanzado' => $limite > 0 && $cajeros->where('esta_activa', true)->count() >= $limite,
            'limitesPorSucursal' => $this->resolverLimitesPorSucursal($negocio, $sucursales, $cajeros),
            'sucursales' => $sucursales,
            'rolesPersonalizados' => $rolesPersonalizados,
            'rolCajeroDefaultId' => $rolCajero?->id,
        ]);
    }

    public function store(Request $request, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $negocioId = app(ContextoNegocio::class)->id();
        $negocio = Negocio::with('membresia.plan')->findOrFail($negocioId);

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => ['required', 'email', 'unique:usuarios,correo'],
            'clave' => 'required|string|min:8',
            'pin' => ['required', 'digits:4'],
            'sucursal_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('sucursales', 'id')->where('negocio_id', $negocioId)],
            'rol_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->where(fn ($q) => $q->where('negocio_id', $negocioId)->where('es_sistema', false))],
            'cuadre_activo' => 'nullable|boolean',
            'aprobacion_activa' => 'nullable|boolean',
        ]);

        $sucursal = Sucursal::findOrFail($datos['sucursal_id']);
        $limiteSucursal = (int) $sucursal->n_cajeros_contratados;
        $limiteGlobal = $this->resolverLimiteCajeros($negocio);

        if ($limiteSucursal > 0) {
            $activosEnSucursal = MembresiaNegocio::where('negocio_id', $negocioId)
                ->where('rol', 'cajero')
                ->where('sucursal_id', $sucursal->id)
                ->where('esta_activa', true)
                ->count();

            abort_if($activosEnSucursal >= $limiteSucursal, 422, "Límite de cajeros alcanzado en {$sucursal->nombre} ({$limiteSucursal}).");
        } else {
            $activos = MembresiaNegocio::where('negocio_id', $negocioId)
                ->where('rol', 'cajero')
                ->where('esta_activa', true)
                ->count();

            abort_if($limiteGlobal > 0 && $activos >= $limiteGlobal, 422, "Límite de cajeros alcanzado ({$limiteGlobal}).");
        }

        $usuario = new User();
        $usuario->nombre = $datos['nombre'];
        $usuario->correo = $datos['correo'];
        $usuario->password = $datos['clave'];
        $usuario->pin = $datos['pin'];
        $usuario->esta_activo = true;
        $usuario->save();

        $rolCajero = Rol::where('slug', 'cajero')
            ->where(fn ($q) => $q->where('negocio_id', $negocioId)->orWhereNull('negocio_id'))
            ->orderByDesc('negocio_id')
            ->first();

        MembresiaNegocio::create([
            'negocio_id' => $negocioId,
            'usuario_id' => $usuario->id,
            'rol' => 'cajero',
            'rol_id' => $datos['rol_id'] ?? $rolCajero?->id,
            'sucursal_id' => $datos['sucursal_id'],
            'esta_activa' => true,
            'cuadre_activo' => $request->boolean('cuadre_activo'),
            'aprobacion_activa' => $request->boolean('aprobacion_activa'),
        ]);

        $auditoria->registrar('cajeros', 'crear', 'Cajero creado', [
            'usuario_id' => $usuario->id,
            'sucursal_id' => $datos['sucursal_id'],
        ], MembresiaNegocio::class, $usuario->id);

        return redirect()->route('cajeros.index')->with('success', 'Cajero creado.');
    }

    public function update(Request $request, User $cajero, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $membresia = $this->validarCajeroDelNegocio($cajero);
        $negocioId = app(ContextoNegocio::class)->id();

        $miembro = MembresiaNegocio::where('negocio_id', $negocioId)
            ->where('usuario_id', auth()->id())
            ->where('esta_activa', true)
            ->first();

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => ['required', 'email', Rule::unique('usuarios', 'correo')->ignore($cajero->id)],
            'pin' => 'nullable|string|digits:4',
            'sucursal_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('sucursales', 'id')->where('negocio_id', $negocioId)],
            'rol_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->where(fn ($q) => $q->where('negocio_id', $negocioId)->where('es_sistema', false))],
            'cuadre_activo' => 'nullable|boolean',
            'aprobacion_activa' => 'nullable|boolean',
        ]);

        if ($miembro?->rol === 'admin_bar') {
            abort_unless(
                $miembro->sucursal_id === $membresia->sucursal_id,
                403,
                'Solo puedes actualizar cajeros de tu propia sucursal.'
            );

            abort_unless(
                $miembro->sucursal_id === null || (int) $datos['sucursal_id'] === $miembro->sucursal_id,
                403,
                'Solo puedes asignar cajeros a tu propia sucursal.'
            );
        }

        if ((int) $datos['sucursal_id'] !== $membresia->sucursal_id) {
            $this->validarLimitesDeSucursalDestino($negocioId, $cajero->id, (int) $datos['sucursal_id']);
        }

        $cajero->nombre = $datos['nombre'];
        $cajero->correo = $datos['correo'];
        if ($request->filled('pin')) {
            $cajero->pin = $datos['pin'];
        }
        $cajero->save();

        $rolCajero = Rol::where('slug', 'cajero')
            ->where(fn ($q) => $q->where('negocio_id', $negocioId)->orWhereNull('negocio_id'))
            ->orderByDesc('negocio_id')
            ->first();

        MembresiaNegocio::where('negocio_id', $negocioId)
            ->where('usuario_id', $cajero->id)
            ->where('rol', 'cajero')
            ->update([
                'sucursal_id' => $datos['sucursal_id'],
                'rol_id' => $datos['rol_id'] ?? $rolCajero?->id,
                'cuadre_activo' => $request->boolean('cuadre_activo'),
                'aprobacion_activa' => $request->boolean('aprobacion_activa'),
            ]);

        $auditoria->registrar('cajeros', 'actualizar', 'Cajero actualizado', [
            'usuario_id' => $cajero->id,
            'sucursal_id' => $datos['sucursal_id'],
        ], MembresiaNegocio::class, $cajero->id);

        return redirect()->route('cajeros.index')->with('success', 'Cajero actualizado.');
    }

    public function destroy(User $cajero, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $this->validarCajeroDelNegocio($cajero);

        abort_if(
            TurnoCaja::where('usuario_id', $cajero->id)->where('estado', 'abierta')->exists(),
            422,
            'No se puede desactivar un cajero con un turno de caja abierto.'
        );

        $negocioId = app(ContextoNegocio::class)->id();

        MembresiaNegocio::where('negocio_id', $negocioId)
            ->where('usuario_id', $cajero->id)
            ->where('rol', 'cajero')
            ->update(['esta_activa' => false]);

        $auditoria->registrar('cajeros', 'desactivar', 'Cajero desactivado', [
            'usuario_id' => $cajero->id,
        ], MembresiaNegocio::class, $cajero->id);

        return redirect()->route('cajeros.index')->with('success', 'Cajero desactivado.');
    }

    private function validarCajeroDelNegocio(User $cajero): MembresiaNegocio
    {
        $negocioId = app(ContextoNegocio::class)->id();

        $membresia = MembresiaNegocio::where('negocio_id', $negocioId)
            ->where('usuario_id', $cajero->id)
            ->where('rol', 'cajero')
            ->first();

        abort_unless($membresia, 404, 'El cajero no pertenece a este bar.');

        return $membresia;
    }

    private function validarLimitesDeSucursalDestino(int $negocioId, int $usuarioId, int $sucursalDestinoId): void
    {
        $sucursal = Sucursal::findOrFail($sucursalDestinoId);
        $limiteSucursal = (int) $sucursal->n_cajeros_contratados;
        $negocio = Negocio::with('membresia.plan')->findOrFail($negocioId);

        if ($limiteSucursal > 0) {
            $activosEnSucursal = MembresiaNegocio::where('negocio_id', $negocioId)
                ->where('rol', 'cajero')
                ->where('sucursal_id', $sucursal->id)
                ->where('esta_activa', true)
                ->where('usuario_id', '!=', $usuarioId)
                ->count();

            abort_if($activosEnSucursal >= $limiteSucursal, 422, "Límite de cajeros alcanzado en {$sucursal->nombre} ({$limiteSucursal}).");
            return;
        }

        $limiteGlobal = $this->resolverLimiteCajeros($negocio);

        if ($limiteGlobal > 0) {
            $activos = MembresiaNegocio::where('negocio_id', $negocioId)
                ->where('rol', 'cajero')
                ->where('esta_activa', true)
                ->where('usuario_id', '!=', $usuarioId)
                ->count();

            abort_if($activos >= $limiteGlobal, 422, "Límite de cajeros alcanzado ({$limiteGlobal}).");
        }
    }

    private function resolverLimiteCajeros(Negocio $negocio): int
    {
        $propietario = MembresiaNegocio::where('negocio_id', $negocio->id)
            ->where('rol', 'propietario')
            ->where('esta_activa', true)
            ->first();

        if ($propietario && $propietario->limite_cajeros > 0) {
            return (int) $propietario->limite_cajeros;
        }

        return (int) ($negocio->membresia?->plan?->limite_cajeros ?? 0);
    }

    private function resolverLimitesPorSucursal(Negocio $negocio, $sucursales, $cajeros): array
    {
        $limites = [];

        foreach ($sucursales as $sucursal) {
            $limite = (int) $sucursal->n_cajeros_contratados;

            if ($limite <= 0) {
                $limite = $this->resolverLimiteCajeros($negocio);
            }

            $limites[$sucursal->id] = [
                'limite' => $limite,
                'activos' => $cajeros
                    ->where('sucursal_id', $sucursal->id)
                    ->where('esta_activa', true)
                    ->count(),
            ];
        }

        return $limites;
    }
}