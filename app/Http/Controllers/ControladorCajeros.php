<?php

namespace App\Http\Controllers;

use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\User;
use App\Services\ContextoNegocio;
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

        $cajeros = MembresiaNegocio::with('usuario')
            ->where('negocio_id', $negocioId)
            ->where('rol', 'cajero')
            ->orderByDesc('esta_activa')
            ->get();

        $limite = $this->resolverLimiteCajeros($negocio);

        return view('cajeros.index', [
            'cajeros' => $cajeros,
            'limiteCajeros' => $limite,
            'limiteAlcanzado' => $limite > 0 && $cajeros->where('esta_activa', true)->count() >= $limite,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $negocioId = app(ContextoNegocio::class)->id();
        $negocio = Negocio::with('membresia.plan')->findOrFail($negocioId);
        $limite = $this->resolverLimiteCajeros($negocio);

        $activos = MembresiaNegocio::where('negocio_id', $negocioId)
            ->where('rol', 'cajero')
            ->where('esta_activa', true)
            ->count();

        abort_if($limite > 0 && $activos >= $limite, 422, "Límite de cajeros alcanzado ({$limite}).");

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => ['required', 'email', 'unique:usuarios,correo'],
            'clave' => 'required|string|min:8',
            'pin' => ['required', 'digits:4'],
            'cuadre_activo' => 'nullable|boolean',
            'aprobacion_activa' => 'nullable|boolean',
        ]);

        $usuario = User::create([
            'nombre' => $datos['nombre'],
            'correo' => $datos['correo'],
            'password' => $datos['clave'],
            'pin' => $datos['pin'],
            'rol' => 'cajero',
            'esta_activo' => true,
        ]);

        MembresiaNegocio::create([
            'negocio_id' => $negocioId,
            'usuario_id' => $usuario->id,
            'rol' => 'cajero',
            'esta_activa' => true,
            'cuadre_activo' => $request->boolean('cuadre_activo'),
            'aprobacion_activa' => $request->boolean('aprobacion_activa'),
        ]);

        return redirect()->route('cajeros.index')->with('success', 'Cajero creado.');
    }

    public function update(Request $request, User $cajero): RedirectResponse
    {
        $this->validarCajeroDelNegocio($cajero);
        $negocioId = app(ContextoNegocio::class)->id();

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => ['required', 'email', Rule::unique('usuarios', 'correo')->ignore($cajero->id)],
            'pin' => 'nullable|string|digits:4',
            'cuadre_activo' => 'nullable|boolean',
            'aprobacion_activa' => 'nullable|boolean',
        ]);

        $cajero->nombre = $datos['nombre'];
        $cajero->correo = $datos['correo'];
        if ($request->filled('pin')) {
            $cajero->pin = $datos['pin'];
        }
        $cajero->save();

        MembresiaNegocio::where('negocio_id', $negocioId)
            ->where('usuario_id', $cajero->id)
            ->where('rol', 'cajero')
            ->update([
                'cuadre_activo' => $request->boolean('cuadre_activo'),
                'aprobacion_activa' => $request->boolean('aprobacion_activa'),
            ]);

        return redirect()->route('cajeros.index')->with('success', 'Cajero actualizado.');
    }

    public function destroy(User $cajero): RedirectResponse
    {
        $this->validarCajeroDelNegocio($cajero);

        $negocioId = app(ContextoNegocio::class)->id();

        MembresiaNegocio::where('negocio_id', $negocioId)
            ->where('usuario_id', $cajero->id)
            ->where('rol', 'cajero')
            ->update(['esta_activa' => false]);

        $cajero->update(['esta_activo' => false]);

        return redirect()->route('cajeros.index')->with('success', 'Cajero desactivado.');
    }

    private function validarCajeroDelNegocio(User $cajero): void
    {
        $negocioId = app(ContextoNegocio::class)->id();

        abort_unless(
            MembresiaNegocio::where('negocio_id', $negocioId)
                ->where('usuario_id', $cajero->id)
                ->where('rol', 'cajero')
                ->exists(),
            404,
            'El cajero no pertenece a este bar.'
        );
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
}