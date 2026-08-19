@extends('layouts.sidebar')

@section('title', 'Rol: ' . $rol->nombre)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">
        <i class="bi bi-shield-lock"></i> {{ $rol->nombre }}
        @if($rol->es_sistema)
            <span class="badge bg-warning text-dark ms-2">Sistema</span>
        @else
            <span class="badge bg-secondary ms-2">Personalizado</span>
        @endif
    </h4>
    <div class="d-flex gap-2">
        <a href="{{ route('roles.edit', $rol) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header"><strong>Información</strong></div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width:140px">Nombre</td>
                        <td class="fw-semibold">{{ $rol->nombre }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Slug</td>
                        <td><code>{{ $rol->slug }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Descripción</td>
                        <td>{{ $rol->descripcion ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tipo</td>
                        <td>{{ $rol->es_sistema ? 'Rol del sistema (no eliminable)' : 'Rol personalizado' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Permisos</td>
                        <td><span class="badge bg-info">{{ $rol->permisos->count() }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header"><strong>Permisos por Módulo</strong></div>
            <div class="card-body">
                @forelse($permisosPorModulo as $modulo => $perms)
                    <div class="mb-3">
                        <h6 class="text-primary">{{ $modulo }}</h6>
                        @foreach($perms as $perm)
                            <span class="badge bg-success me-1 mb-1">{{ $perm->nombre }}</span>
                        @endforeach
                    </div>
                @empty
                    <p class="text-muted mb-0">Sin permisos asignados.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
