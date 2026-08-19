@extends('layouts.sidebar')

@section('title', 'Sucursales')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3">Sucursales</h1>
        <p class="text-muted mb-0">{{ $sucursales->where('esta_activa', true)->count() }} de {{ $xns > 0 ? $xns : '∞' }} sucursales contratadas.</p>
    </div>
    @if ($xns <= 0 || $sucursales->where('esta_activa', true)->count() < $xns)
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearSucursal">
            <i class="bi bi-plus-circle"></i> Nueva sucursal
        </button>
    @endif
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm rounded">
        <thead class="table-light">
            <tr>
                <th>Nombre</th>
                <th>Ubicación</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Cajeros</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sucursales as $sucursal)
                <tr>
                    <td class="fw-semibold">{{ $sucursal->nombre }}</td>
                    <td>
                        @if ($sucursal->provincia || $sucursal->canton || $sucursal->ciudad)
                            {{ collect([$sucursal->ciudad, $sucursal->canton, $sucursal->provincia])->filter()->implode(', ') }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $sucursal->direccion ?? '—' }}</td>
                    <td>{{ $sucursal->telefono ?? '—' }}</td>
                    <td>{{ $sucursal->n_cajeros_contratados > 0 ? $sucursal->n_cajeros_contratados : '∞' }}</td>
                    <td>
                        @if ($sucursal->esta_activa)
                            <span class="badge bg-success">Activa</span>
                        @else
                            <span class="badge bg-secondary">Inactiva</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editar{{ $sucursal->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('sucursales.destroy', $sucursal) }}" class="d-inline" onsubmit="return confirm('¿Eliminar esta sucursal?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No hay sucursales registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="modal fade" id="crearSucursal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('sucursales.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nueva sucursal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Provincia</label>
                            <input type="text" name="provincia" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cantón</label>
                            <input type="text" name="canton" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ciudad</label>
                            <input type="text" name="ciudad" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cajeros contratados (xNC)</label>
                        <input type="number" name="n_cajeros_contratados" min="0" max="50" class="form-control" value="1">
                        <div class="form-text">0 = ilimitado.</div>
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

@foreach ($sucursales as $sucursal)
    <div class="modal fade" id="editar{{ $sucursal->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('sucursales.update', $sucursal) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Editar sucursal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" value="{{ $sucursal->nombre }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="direccion" class="form-control" value="{{ $sucursal->direccion }}">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Provincia</label>
                                <input type="text" name="provincia" class="form-control" value="{{ $sucursal->provincia }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cantón</label>
                                <input type="text" name="canton" class="form-control" value="{{ $sucursal->canton }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ciudad</label>
                                <input type="text" name="ciudad" class="form-control" value="{{ $sucursal->ciudad }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="{{ $sucursal->telefono }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cajeros contratados (xNC)</label>
                            <input type="number" name="n_cajeros_contratados" min="0" max="50" class="form-control" value="{{ $sucursal->n_cajeros_contratados }}">
                            <div class="form-text">0 = ilimitado.</div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="esta_activa" id="activa{{ $sucursal->id }}" value="1" @checked($sucursal->esta_activa)>
                            <label class="form-check-label" for="activa{{ $sucursal->id }}">Activa</label>
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