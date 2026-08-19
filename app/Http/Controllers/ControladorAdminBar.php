<?php

namespace App\Http\Controllers;

use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\ContextoNegocio;
use App\Services\RegistradorAuditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorAdminBar extends Controller
{
    public function index(): View
    {
        $negocioId = app(ContextoNegocio::class)->id();

        $administradores = MembresiaNegocio::with('usuario', 'sucursal')
            ->where('negocio_id', $negocioId)
            ->where('rol', 'admin_bar')
            ->orderByDesc('esta_activa')
            ->get();

        return view('admin-bar.index', [
            'administradores' => $administradores,
            'sucursales' => Sucursal::where('negocio_id', $negocioId)->where('esta_activa', true)->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $negocioId = app(ContextoNegocio::class)->id();

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => ['required', 'email', 'unique:usuarios,correo'],
            'clave' => 'required|string|min:8',
            'sucursal_id' => ['required', 'integer', Rule::exists('sucursales', 'id')->where('negocio_id', $negocioId)],
        ]);

        $yaExiste = MembresiaNegocio::where('negocio_id', $negocioId)
            ->where('rol', 'admin_bar')
            ->where('sucursal_id', $datos['sucursal_id'])
            ->where('esta_activa', true)
            ->exists();

        abort_if($yaExiste, 422, 'Esta sucursal ya tiene un administrador de bar asignado (máximo 1 por sucursal).');

        $usuario = new User();
        $usuario->nombre = $datos['nombre'];
        $usuario->correo = $datos['correo'];
        $usuario->password = $datos['clave'];
        $usuario->debe_cambiar_password = true;
        $usuario->esta_activo = true;
        $usuario->save();

        $rolAdmin = Rol::where('slug', 'admin_bar')
            ->where(fn ($q) => $q->where('negocio_id', $negocioId)->orWhereNull('negocio_id'))
            ->orderByDesc('negocio_id')
            ->first();

        MembresiaNegocio::create([
            'negocio_id' => $negocioId,
            'usuario_id' => $usuario->id,
            'rol' => 'admin_bar',
            'rol_id' => $rolAdmin?->id,
            'sucursal_id' => $datos['sucursal_id'],
            'esta_activa' => true,
        ]);

        $auditoria->registrar('admin_bar', 'crear', 'Administrador de bar creado', [
            'usuario_id' => $usuario->id,
            'sucursal_id' => $datos['sucursal_id'],
        ], MembresiaNegocio::class, $usuario->id);

        return redirect()->route('admin-bar.index')->with('success', 'Administrador de bar creado.');
    }

    public function update(Request $request, User $admin, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $this->validarAdminDelNegocio($admin);
        $negocioId = app(ContextoNegocio::class)->id();

        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => ['required', 'email', Rule::unique('usuarios', 'correo')->ignore($admin->id)],
            'clave' => 'nullable|string|min:8',
            'sucursal_id' => ['required', 'integer', Rule::exists('sucursales', 'id')->where('negocio_id', $negocioId)],
            'esta_activa' => 'nullable|boolean',
        ]);

        $ocupada = MembresiaNegocio::where('negocio_id', $negocioId)
            ->where('rol', 'admin_bar')
            ->where('sucursal_id', $datos['sucursal_id'])
            ->where('usuario_id', '!=', $admin->id)
            ->where('esta_activa', true)
            ->exists();

        abort_if($ocupada, 422, 'Esta sucursal ya tiene otro administrador de bar asignado.');

        $admin->nombre = $datos['nombre'];
        $admin->correo = $datos['correo'];
        if ($request->filled('clave')) {
            $admin->password = $datos['clave'];
        }
        $admin->esta_activo = $request->boolean('esta_activa');
        $admin->save();

        MembresiaNegocio::where('negocio_id', $negocioId)
            ->where('usuario_id', $admin->id)
            ->where('rol', 'admin_bar')
            ->update([
                'sucursal_id' => $datos['sucursal_id'],
                'esta_activa' => $request->boolean('esta_activa'),
            ]);

        $auditoria->registrar('admin_bar', 'actualizar', 'Administrador de bar actualizado', [
            'usuario_id' => $admin->id,
            'esta_activa' => $request->boolean('esta_activa'),
        ], MembresiaNegocio::class, $admin->id);

        return redirect()->route('admin-bar.index')->with('success', 'Administrador de bar actualizado.');
    }

    public function destroy(User $admin, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $this->validarAdminDelNegocio($admin);

        MembresiaNegocio::where('negocio_id', app(ContextoNegocio::class)->id())
            ->where('usuario_id', $admin->id)
            ->where('rol', 'admin_bar')
            ->update(['esta_activa' => false]);

        $auditoria->registrar('admin_bar', 'desactivar', 'Administrador de bar desactivado', [
            'usuario_id' => $admin->id,
        ], MembresiaNegocio::class, $admin->id);

        return redirect()->route('admin-bar.index')->with('success', 'Administrador de bar desactivado.');
    }

    private function validarAdminDelNegocio(User $admin): void
    {
        abort_unless(
            MembresiaNegocio::where('negocio_id', app(ContextoNegocio::class)->id())
                ->where('usuario_id', $admin->id)
                ->where('rol', 'admin_bar')
                ->exists(),
            404,
            'El administrador no pertenece a este bar.'
        );
    }
}