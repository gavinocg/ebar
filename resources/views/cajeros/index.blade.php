@extends('layouts.sidebar')

@section('title', 'Cajeros')

@section('content')
@php
    $puedeCrear = auth()->user()->esPropietario();
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3">Cajeros</h1>
        <p class="text-muted mb-0">
            {{ $cajeros->where('esta_activa', true)->count() }} de {{ $limiteCajeros }} cajeros activos según la configuración de tu bar.
        </p>
    </div>
    @if ($puedeCrear)
        @unless ($limiteAlcanzado)
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearCajero">
                <i class="bi bi-person-plus"></i> Nuevo cajero
            </button>
        @else
            <span class="badge bg-warning text-dark">Límite de cajeros alcanzado</span>
        @endif
    @endif
</div>

@if ($limiteCajeros > 0)
    <div class="col-md-3">
        <div class="card bg-white shadow-sm">
            <div class="card-body py-2">
                <div class="small text-muted">Cajeros activos</div>
                <div class="fw-bold">
                    {{ $cajeros->where('esta_activa', true)->count() }} / {{ $limiteCajeros }}
                </div>
            </div>
        </div>
    </div>
@endif

<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm rounded">
        <thead class="table-light">
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>PIN</th>
                <th>Sucursal</th>
                <th>Cuadre</th>
                <th>Aprobación</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cajeros as $cajero)
                <tr>
                    <td class="fw-semibold">{{ $cajero->usuario->nombre }}</td>
                    <td>{{ $cajero->usuario->correo }}</td>
                    <td>
                        @if ($cajero->usuario->pin)
                            <span class="text-muted">definido</span>
                        @else
                            <span class="badge bg-secondary">sin PIN</span>
                        @endif
                    </td>
                    <td>
                        @if ($cajero->sucursal)
                            {{ $cajero->sucursal->nombre }}
                        @else
                            <span class="badge bg-secondary">Sin asignar</span>
                        @endif
                    </td>
                    <td>
                        @if ($cajero->cuadre_activo)
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-secondary">Desactivado</span>
                        @endif
                    </td>
                    <td>
                        @if ($cajero->aprobacion_activa)
                            <span class="badge bg-success">Requerida</span>
                        @else
                            <span class="badge bg-secondary">Directo</span>
                        @endif
                    </td>
                    <td>
                        @if ($cajero->esta_activa)
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-secondary">Desactivado</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editar{{ $cajero->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @if ($puedeCrear)
                            <form method="POST" action="{{ route('cajeros.destroy', $cajero->usuario) }}" class="d-inline" onsubmit="return confirm('¿Desactivar este cajero? Conservará su historial.')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-person-x"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No hay cajeros registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="modal fade" id="crearCajero" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('cajeros.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo cajero</h5>
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
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PIN de 4 dígitos *</label>
                        <input type="password" name="pin" class="form-control" inputmode="numeric" maxlength="4" required>
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
                    @if($rolesPersonalizados->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label">Rol</label>
                            <select name="rol_id" class="form-select">
                                <option value="">Rol de cajero (predeterminado)</option>
                                @foreach($rolesPersonalizados as $rolPersonalizado)
                                    <option value="{{ $rolPersonalizado->id }}">{{ $rolPersonalizado->nombre }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Define los permisos que tendrá este cajero.</small>
                        </div>
                    @endif
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="cuadre_activo" id="cuadre_crear" value="1" checked>
                        <label class="form-check-label" for="cuadre_crear">Cuadre de turno activo (conteo de billetes/monedas)</label>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="aprobacion_activa" id="aprobacion_crear" value="1" checked>
                        <label class="form-check-label" for="aprobacion_crear">Requiere visto bueno del administrador</label>
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

@foreach ($cajeros as $cajero)
    <div class="modal fade" id="editar{{ $cajero->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('cajeros.update', $cajero->usuario) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Editar cajero</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" value="{{ $cajero->usuario->nombre }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo *</label>
                            <input type="email" name="correo" class="form-control" value="{{ $cajero->usuario->correo }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nuevo PIN (opcional)</label>
                            <input type="password" name="pin" class="form-control" inputmode="numeric" maxlength="4">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sucursal *</label>
                            <select name="sucursal_id" class="form-select" required>
                                <option value="">Seleccionar sucursal...</option>
                                @foreach($sucursales as $sucursal)
                                    <option value="{{ $sucursal->id }}" @selected((int)$cajero->sucursal_id === $sucursal->id)>{{ $sucursal->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($rolesPersonalizados->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label">Rol</label>
                                <select name="rol_id" class="form-select">
                                    <option value="" @selected(!$cajero->rol_id || $cajero->rol_id === $rolCajeroDefaultId)>Rol de cajero (predeterminado)</option>
                                    @foreach($rolesPersonalizados as $rolPersonalizado)
                                        <option value="{{ $rolPersonalizado->id }}" @selected((int)$cajero->rol_id === $rolPersonalizado->id)>{{ $rolPersonalizado->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="cuadre_activo" id="cuadre_{{ $cajero->id }}" value="1" @checked($cajero->cuadre_activo)>
                            <label class="form-check-label" for="cuadre_{{ $cajero->id }}">Cuadre de turno activo (conteo de billetes/monedas)</label>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="aprobacion_activa" id="aprobacion_{{ $cajero->id }}" value="1" @checked($cajero->aprobacion_activa)>
                            <label class="form-check-label" for="aprobacion_{{ $cajero->id }}">Requiere visto bueno del administrador</label>
                        </div>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="esta_activa" id="estado_{{ $cajero->id }}" value="1" @checked($cajero->esta_activa)>
                            <label class="form-check-label" for="estado_{{ $cajero->id }}">Cajero activo (INACTIVO no podrá abrir turnos)</label>
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