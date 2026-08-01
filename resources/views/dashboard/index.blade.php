@extends('layouts.sidebar')

@section('title', 'Panel')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <div class="text-uppercase small text-muted fw-semibold">Control de efectivo</div>
            @if($turnoCaja)
                <h5 class="mb-1"><span class="badge bg-success me-2">Caja abierta</span>{{ $turnoCaja->caja->nombre }}</h5>
                <p class="text-muted mb-0">Abierta {{ $turnoCaja->abierto_en->format('d/m/Y H:i') }} · Fondo inicial: ${{ number_format($turnoCaja->fondo_inicial, 2) }}</p>
            @else
                <h5 class="mb-1"><span class="badge bg-secondary me-2">Caja cerrada</span>Sin turno activo</h5>
                <p class="text-muted mb-0">Abre la caja antes de registrar ventas.</p>
            @endif
        </div>
        @if($turnoCaja)
            <form method="POST" action="{{ route('caja.cerrar') }}" class="d-flex gap-2 align-items-end">
                @csrf
                <div>
                    <label class="form-label small mb-1" for="efectivo_contado">Efectivo contado</label>
                    <input class="form-control" id="efectivo_contado" name="efectivo_contado" type="number" min="0" step="0.01" required>
                </div>
                <button class="btn btn-outline-danger" type="submit"><i class="bi bi-lock"></i> Cerrar caja</button>
            </form>
        @elseif($cajaActiva)
            <form method="POST" action="{{ route('caja.abrir') }}" class="d-flex gap-2 align-items-end">
                @csrf
                <div>
                    <label class="form-label small mb-1" for="fondo_inicial">Fondo inicial</label>
                    <input class="form-control" id="fondo_inicial" name="fondo_inicial" type="number" min="0" step="0.01" value="0.00" required>
                </div>
                <button class="btn btn-dark" type="submit"><i class="bi bi-unlock"></i> Abrir caja</button>
            </form>
        @endif
    </div>
</div>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6 class="card-title">Ventas Hoy</h6>
                <h2 class="mb-0">{{ $salesToday }}</h2>
                <small>${{ number_format($revenueToday, 2) }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6 class="card-title">Ventas del Mes</h6>
                <h2 class="mb-0">{{ $salesMonth }}</h2>
                <small>${{ number_format($revenueMonth, 2) }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h6 class="card-title">Productos</h6>
                <h2 class="mb-0">{{ $productsCount }}</h2>
                <small>{{ $categoriesCount }} categorías</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h6 class="card-title">Stock Bajo</h6>
                <h2 class="mb-0">{{ $lowStockProducts->count() }}</h2>
                <small>Productos ≤ 10 unidades</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ventas Recientes</h5>
            </div>
            <div class="card-body">
                @if($recentSales->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Ticket</th>
                                    <th>Total</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentSales as $sale)
                                    <tr>
                                        <td>{{ $sale->numero_comprobante }}</td>
                                        <td>${{ number_format($sale->total, 2) }}</td>
                                        <td>{{ $sale->created_at->format('d/m H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">No hay ventas aún</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Productos con Stock Bajo</h5>
            </div>
            <div class="card-body">
                @if($lowStockProducts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStockProducts as $product)
                                    <tr>
                                        <td>{{ $product->nombre }}</td>
                                        <td>
                                            <span class="badge bg-{{ $product->existencias == 0 ? 'danger' : 'warning' }}">
                                                {{ $product->existencias }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">Todo el inventario está bien surtido</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
