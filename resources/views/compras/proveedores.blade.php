@extends('layouts.sidebar')

@section('title', 'Proveedores')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Proveedores</h4>
    <a href="{{ route('ordenes.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-cart-plus"></i> Órdenes de compra
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Nuevo proveedor</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('proveedores.store') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Nombre *</label>
                <input type="text" name="nombre" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">RUC</label>
                <input type="text" name="ruc" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Teléfono</label>
                <input type="text" name="telefono" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Correo</label>
                <input type="email" name="correo" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Dirección</label>
                <input type="text" name="direccion" class="form-control">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button class="btn btn-primary"><i class="bi bi-plus-circle"></i> Guardar proveedor</button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body">
        @if($proveedores->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>RUC</th>
                            <th>Teléfono</th>
                            <th>Correo</th>
                            <th>Dirección</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($proveedores as $proveedor)
                            <tr>
                                <td class="fw-semibold">{{ $proveedor->nombre }}</td>
                                <td>{{ $proveedor->ruc ?? '—' }}</td>
                                <td>{{ $proveedor->telefono ?? '—' }}</td>
                                <td>{{ $proveedor->correo ?? '—' }}</td>
                                <td>{{ $proveedor->direccion ?? '—' }}</td>
                                <td>
                                    @if($proveedor->esta_activo)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editar{{ $proveedor->id }}"><i class="bi bi-pencil"></i></button>
                                    <form action="{{ route('proveedores.destroy', $proveedor) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar proveedor?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>

                            <div class="modal fade" id="editar{{ $proveedor->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('proveedores.update', $proveedor) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Editar proveedor</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Nombre *</label>
                                                    <input type="text" name="nombre" class="form-control" value="{{ $proveedor->nombre }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">RUC</label>
                                                    <input type="text" name="ruc" class="form-control" value="{{ $proveedor->ruc }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Teléfono</label>
                                                    <input type="text" name="telefono" class="form-control" value="{{ $proveedor->telefono }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Correo</label>
                                                    <input type="email" name="correo" class="form-control" value="{{ $proveedor->correo }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Dirección</label>
                                                    <input type="text" name="direccion" class="form-control" value="{{ $proveedor->direccion }}">
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="esta_activo" value="0">
                                                    <input type="checkbox" name="esta_activo" class="form-check-input" value="1" {{ $proveedor->esta_activo ? 'checked' : '' }}>
                                                    <label class="form-check-label">Proveedor activo</label>
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
            <p class="text-muted text-center">No hay proveedores registrados.</p>
        @endif
    </div>
</div>
@endsection