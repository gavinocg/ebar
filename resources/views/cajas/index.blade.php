@extends('layouts.sidebar')

@section('title', 'Cajas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Cajas</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaCaja">
        <i class="bi bi-plus-circle"></i> Nueva caja
    </button>
</div>

@if($limiteAlcanzado)
    <div class="alert alert-warning">
        Alcanzaste el límite de cajas de tu plan ({{ $limiteCajas }}).
    </div>
@endif

<div class="card">
    <div class="card-body">
        @if($cajas->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Sucursal</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cajas as $caja)
                            <tr>
                                <td class="fw-semibold">{{ $caja->nombre }}</td>
                                <td>{{ $caja->sucursal?->nombre ?? 'Todas' }}</td>
                                <td>
                                    @if($caja->esta_activa)
                                        <span class="badge bg-success">Activa</span>
                                    @else
                                        <span class="badge bg-secondary">Inactiva</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editar{{ $caja->id }}"><i class="bi bi-pencil"></i></button>
                                    <form action="{{ route('cajas.destroy', $caja) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar caja?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>

                            <div class="modal fade" id="editar{{ $caja->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('cajas.update', $caja) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Editar caja</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Nombre *</label>
                                                    <input type="text" name="nombre" class="form-control" value="{{ $caja->nombre }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Sucursal</label>
                                                    <select name="sucursal_id" class="form-select">
                                                        <option value="">Todas las sucursales</option>
                                                        @foreach($sucursales as $sucursal)
                                                            <option value="{{ $sucursal->id }}" @selected($caja->sucursal_id === $sucursal->id)>{{ $sucursal->nombre }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="esta_activa" value="0">
                                                    <input type="checkbox" name="esta_activa" class="form-check-input" value="1" {{ $caja->esta_activa ? 'checked' : '' }}>
                                                    <label class="form-check-label">Caja activa</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button class="btn btn-primary">Guardar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted text-center">No hay cajas configuradas.</p>
        @endif
    </div>
</div>

<div class="modal fade" id="nuevaCaja" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('cajas.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nueva caja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Ej: Caja principal">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sucursal</label>
                        <select name="sucursal_id" class="form-select">
                            <option value="">Todas las sucursales</option>
                            @foreach($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection