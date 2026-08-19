@extends('layouts.sidebar')

@section('title', 'Administradores de bar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3">Administradores de bar</h1>
        <p class="text-muted mb-0">Máximo 1 administrador de bar por sucursal.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearAdminBar">
        <i class="bi bi-person-plus"></i> Nuevo administrador
    </button>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm rounded">
        <thead class="table-light">
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Sucursal</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($administradores as $admin)
                <tr>
                    <td class="fw-semibold">{{ $admin->usuario->nombre }}</td>
                    <td>{{ $admin->usuario->correo }}</td>
                    <td>{{ $admin->sucursal?->nombre ?? '—' }}</td>
                    <td>
                        @if ($admin->esta_activa)
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-secondary">Desactivado</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editar{{ $admin->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('admin-bar.destroy', $admin->usuario) }}" class="d-inline" onsubmit="return confirm('¿Desactivar este administrador?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-person-x"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">No hay administradores de bar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="modal fade" id="crearAdminBar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin-bar.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo administrador de bar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo *</label>
                        <input type="email" name="correo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" name="clave" class="form-control" required>
                        <div class="form-text">El administrador deberá cambiarla en su primer ingreso.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sucursal *</label>
                        <select name="sucursal_id" class="form-select" required>
                            <option value="">Seleccionar sucursal...</option>
                            @foreach($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach ($administradores as $admin)
    <div class="modal fade" id="editar{{ $admin->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin-bar.update', $admin->usuario) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Editar administrador</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" value="{{ $admin->usuario->nombre }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo *</label>
                            <input type="email" name="correo" class="form-control" value="{{ $admin->usuario->correo }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nueva contraseña (opcional)</label>
                            <input type="password" name="clave" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sucursal *</label>
                            <select name="sucursal_id" class="form-select" required>
                                <option value="">Seleccionar sucursal...</option>
                                @foreach($sucursales as $sucursal)
                                    <option value="{{ $sucursal->id }}" @selected((int)$admin->sucursal_id === $sucursal->id)>{{ $sucursal->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="esta_activa" id="activa_{{ $admin->id }}" value="1" @checked($admin->esta_activa)>
                            <label class="form-check-label" for="activa_{{ $admin->id }}">Activo</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection