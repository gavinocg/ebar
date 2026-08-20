<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Rol;
use App\Services\ContextoNegocio;
use App\Services\GuardiaEliminacion;
use App\Services\RegistradorAuditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorRoles extends Controller
{
    public function index(): View
    {
        $negocioId = app(ContextoNegocio::class)->id();
        $roles = Rol::porNegocio($negocioId)
            ->withCount('permisos')
            ->orderBy('es_sistema', 'desc')
            ->orderBy('nombre')
            ->get();

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        $permisos = Permission::orderBy('modulo')->orderBy('nombre')->get()->groupBy('modulo');

        return view('roles.create', compact('permisos'));
    }

    public function store(Request $request, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $negocioId = app(ContextoNegocio::class)->id();

        $datos = $request->validate([
            'nombre' => 'required|string|max:50',
            'slug' => [
                'required', 'string', 'max:50', 'regex:/^[a-z_]+$/',
                Rule::unique('roles', 'slug')->where(fn ($q) => $q->where(fn ($qq) => $qq->where('negocio_id', $negocioId)->orWhereNull('negocio_id'))),
            ],
            'descripcion' => 'nullable|string|max:255',
            'permisos' => 'required|array|min:1',
            'permisos.*' => 'integer|exists:permissions,id',
        ]);

        abort_if(
            in_array($datos['slug'], ['super_admin', 'propietario', 'admin_bar', 'cajero'], true),
            422,
            "El slug \"{$datos['slug']}\" pertenece a un rol del sistema."
        );

        $rol = Rol::create([
            'negocio_id' => $negocioId,
            'nombre' => $datos['nombre'],
            'slug' => $datos['slug'],
            'descripcion' => $datos['descripcion'] ?? null,
            'es_sistema' => false,
        ]);

        $rol->permisos()->sync($datos['permisos']);

        $auditoria->registrar('roles', 'crear', "Rol \"{$rol->nombre}\" creado", [
            'rol_id' => $rol->id,
            'permisos' => $datos['permisos'],
        ], Rol::class, $rol->id);

        return redirect()->route('roles.index')->with('success', "Rol \"{$rol->nombre}\" creado.");
    }

    public function show(Rol $rol): View
    {
        $this->validarRolDelNegocio($rol);
        $rol->load('permisos');

        $permisosPorModulo = $rol->permisos->groupBy('modulo');

        return view('roles.show', compact('rol', 'permisosPorModulo'));
    }

    public function edit(Rol $rol): View
    {
        $this->validarRolDelNegocio($rol);

        $todosPermisos = Permission::orderBy('modulo')->orderBy('nombre')->get()->groupBy('modulo');
        $permisosAsignados = $rol->permisos->pluck('id')->toArray();

        return view('roles.edit', compact('rol', 'todosPermisos', 'permisosAsignados'));
    }

    public function update(Request $request, Rol $rol, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $this->validarRolDelNegocio($rol);

        abort_if($rol->es_sistema, 422, 'Los roles del sistema no se pueden editar.');

        $datos = $request->validate([
            'nombre' => 'required|string|max:50',
            'descripcion' => 'nullable|string|max:255',
            'permisos' => 'required|array|min:1',
            'permisos.*' => 'integer|exists:permissions,id',
            'esta_activo' => 'nullable|boolean',
        ]);

        $rol->update([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'esta_activo' => $request->boolean('esta_activo'),
        ]);

        $rol->permisos()->sync($datos['permisos']);

        $auditoria->registrar('roles', 'actualizar', "Rol \"{$rol->nombre}\" actualizado", [
            'rol_id' => $rol->id,
            'permisos' => $datos['permisos'],
        ], Rol::class, $rol->id);

        return redirect()->route('roles.index')->with('success', "Rol \"{$rol->nombre}\" actualizado.");
    }

    public function destroy(Rol $rol, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $this->validarRolDelNegocio($rol);

        abort_if($rol->es_sistema, 422, 'No se puede eliminar un rol del sistema.');

        $dependencias = GuardiaEliminacion::rolConDependencias($rol->id);

        if ($dependencias) {
            return back()->with('no_eliminable', [
                'entidad' => 'rol',
                'dependencias' => array_values(array_unique($dependencias)),
                'url' => route('roles.desactivar', $rol),
            ]);
        }

        $auditoria->registrar('roles', 'eliminar', "Rol \"{$rol->nombre}\" eliminado", [
            'rol_id' => $rol->id,
        ], Rol::class, $rol->id);

        $rol->permisos()->detach();
        $rol->delete();

        return redirect()->route('roles.index')->with('success', 'Rol eliminado.');
    }

    public function desactivar(Rol $rol): RedirectResponse
    {
        $this->validarRolDelNegocio($rol);

        abort_if($rol->es_sistema, 422, 'No se puede desactivar un rol del sistema.');

        $rol->esta_activo = false;
        $rol->save();

        return redirect()->route('roles.index')->with('success', 'Rol desactivado por estar asignado a usuarios activos.');
    }

    private function validarRolDelNegocio(Rol $rol): void
    {
        $negocioId = app(ContextoNegocio::class)->id();

        abort_unless(
            $rol->negocio_id == $negocioId || is_null($rol->negocio_id),
            404,
            'El rol no pertenece a este bar.'
        );
    }
}
