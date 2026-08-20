@extends('layouts.sidebar')

@section('title', 'Roles')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-shield-lock"></i> Roles y Permisos</h4>
    <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Nuevo Rol
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Rol</th>
                        <th>Slug</th>
                        <th>Descripción</th>
                        <th class="text-center">Permisos</th>
                        <th class="text-center">Tipo</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $rol)
                    <tr>
                        <td class="fw-semibold">{{ $rol->nombre }}</td>
                        <td><code>{{ $rol->slug }}</code></td>
                        <td class="text-muted">{{ $rol->descripcion ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge bg-info">{{ $rol->permisos_count }}</span>
                        </td>
                        <td class="text-center">
                            @if($rol->es_sistema)
                                <span class="badge bg-warning text-dark">Sistema</span>
                            @else
                                <span class="badge bg-secondary">Personalizado</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($rol->esta_activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('roles.show', $rol) }}" class="btn btn-sm btn-outline-info" title="Ver">
                                <i class="bi bi-eye"></i>
                            </a>
                            @unless($rol->es_sistema)
                                <a href="{{ route('roles.edit', $rol) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('roles.destroy', $rol) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar el rol {{ $rol->nombre }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No hay roles registrados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
