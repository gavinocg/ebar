@extends('layouts.sidebar')

@section('title', 'Órdenes de compra')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Órdenes de compra</h4>
    <div>
        <a href="{{ route('proveedores.index') }}" class="btn btn-outline-secondary"><i class="bi bi-truck"></i> Proveedores</a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaOrden"><i class="bi bi-plus-circle"></i> Nueva orden</button>
    </div>
</div>

@if($ordenes->count() > 0)
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Artículos</th>
                            <th class="text-end">Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ordenes as $orden)
                            <tr>
                                <td class="fw-semibold">{{ $orden->numero }}</td>
                                <td>{{ $orden->proveedor?->nombre ?? '—' }}</td>
                                <td>{{ $orden->fecha->format('d/m/Y') }}</td>
                                <td>{{ $orden->detalles->sum('cantidad') }}</td>
                                <td class="text-end">${{ number_format($orden->total, 2) }}</td>
                                <td>
                                    @if($orden->estado === 'recibida')
                                        <span class="badge bg-success">Recibida</span>
                                    @else
                                        <span class="badge bg-warning">Pendiente</span>
                                    @endif
                                </td>
                                <td>
                                    @if($orden->estado !== 'recibida')
                                        <form action="{{ route('ordenes.recibir', $orden) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Recibir mercancía y actualizar existencias?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success" title="Recibir mercancía"><i class="bi bi-box-arrow-down"></i> Recibir</button>
                                        </form>
                                        <form action="{{ route('ordenes.destroy', $orden) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar orden?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@else
    <div class="card"><div class="card-body text-center text-muted">No hay órdenes de compra.</div></div>
@endif

<div class="modal fade" id="nuevaOrden" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('ordenes.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nueva orden de compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Proveedor *</label>
                            <select name="proveedor_id" class="form-select" required>
                                @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="{{ now()->toDateString() }}">
                        </div>
                    </div>
                    <div class="mt-3 mb-2 d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0">Artículos</label>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="agregarFila()"><i class="bi bi-plus-lg"></i> Agregar</button>
                    </div>
                    <div id="filasItems">
                        <div class="row g-2 item-fila">
                            <div class="col-md-6">
                                <select name="items[0][producto_id]" class="form-select" required>
                                    @foreach($productos as $producto)
                                        <option value="{{ $producto->id }}">{{ $producto->nombre }} ({{ $producto->existencias }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="items[0][cantidad]" class="form-control" min="1" value="1" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="items[0][precio_unitario]" class="form-control" step="0.01" min="0" value="0" required>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger" onclick="this.closest('.item-fila').remove()"><i class="bi bi-x"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notas" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar orden</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let indiceFila = 1;
const opciones = `@foreach($productos as $producto)<option value="{{ $producto->id }}">{{ $producto->nombre }} ({{ $producto->existencias }})</option>@endforeach`;

function agregarFila() {
    const fila = document.createElement('div');
    fila.className = 'row g-2 item-fila mt-1';
    fila.innerHTML = `
        <div class="col-md-6"><select name="items[${indiceFila}][producto_id]" class="form-select" required>${opciones}</select></div>
        <div class="col-md-2"><input type="number" name="items[${indiceFila}][cantidad]" class="form-control" min="1" value="1" required></div>
        <div class="col-md-3"><input type="number" name="items[${indiceFila}][precio_unitario]" class="form-control" step="0.01" min="0" value="0" required></div>
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger" onclick="this.closest('.item-fila').remove()"><i class="bi bi-x"></i></button></div>`;
    document.getElementById('filasItems').appendChild(fila);
    indiceFila++;
}
</script>
@endpush
@endsection