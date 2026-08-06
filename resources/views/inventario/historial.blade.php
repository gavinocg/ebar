@extends('layouts.sidebar')

@section('title', 'Inventario')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Historial de inventario</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajusteModal">
        <i class="bi bi-sliders"></i> Ajustar existencias
    </button>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Producto</label>
                <select name="producto_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}" @selected(request('producto_id') == $producto->id)>{{ $producto->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="">Todos</option>
                    @foreach (['venta', 'entrada', 'ajuste', 'ajuste_negativo', 'devolucion', 'mercancias'] as $t)
                        <option value="{{ $t }}" @selected(request('tipo') === $t)>{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Sucursal</label>
                <select name="sucursal_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach ($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" @selected(request('sucursal_id') == $sucursal->id)>{{ $sucursal->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($movimientos->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th class="text-end">Cantidad</th>
                            <th class="text-end">Antes</th>
                            <th class="text-end">Después</th>
                            <th>Responsable</th>
                            <th>Nota</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movimientos as $m)
                            <tr>
                                <td>{{ $m->created_at->format('d/m/Y H:i') }}</td>
                                <td class="fw-semibold">{{ $m->producto->nombre }}</td>
                                <td><span class="badge bg-secondary">{{ $m->tipo }}</span></td>
                                <td class="text-end {{ $m->cantidad < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $m->cantidad > 0 ? '+' : '' }}{{ $m->cantidad }}
                                </td>
                                <td class="text-end">{{ $m->existencias_anteriores }}</td>
                                <td class="text-end">{{ $m->existencias_posteriores }}</td>
                                <td>{{ $m->usuario?->nombre ?? '—' }}</td>
                                <td class="text-muted small">{{ $m->notas }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $movimientos->links() }}
        @else
            <p class="text-muted text-center">No hay movimientos registrados.</p>
        @endif
    </div>
</div>

<div class="modal fade" id="ajusteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('inventario.ajustar') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ajustar existencias</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Producto *</label>
                        <select name="producto_id" class="form-select" required>
                            @foreach ($productos->where('maneja_existencias', true) as $producto)
                                <option value="{{ $producto->id }}">{{ $producto->nombre }} ({{ $producto->existencias }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo" class="form-select" required>
                            <option value="entrada">Entrada (+)</option>
                            <option value="ajuste_negativo">Salida (-)</option>
                            <option value="devolucion">Devolución (+)</option>
                            <option value="mercancias">Recepción de mercancía (+)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cantidad *</label>
                        <input type="number" name="cantidad" class="form-control" min="1" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Motivo *</label>
                        <textarea name="motivo" class="form-control" rows="2" maxlength="255" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection