@extends('layouts.sidebar')

@section('title', 'Ranking de Productos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Ranking de Productos</h4>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Fecha Inicio</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Fecha Fin</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6>Total Ventas</h6>
                <h2>{{ number_format($totalVentas) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6>Total Ingresos</h6>
                <h2>${{ number_format($totalIngresos, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h6>Productos Distintos</h6>
                <h2>{{ $productos->count() }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Top Productos</h5>
    </div>
    <div class="card-body">
        @if($productos->isEmpty())
            <p class="text-muted text-center">No hay ventas en el periodo seleccionado.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th class="text-end">Unidades Vendidas</th>
                            <th class="text-end">Ingreso Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productos as $i => $producto)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $producto->nombre_producto }}</td>
                            <td class="text-end"><span class="badge bg-info">{{ number_format($producto->total_vendido) }}</span></td>
                            <td class="text-end"><strong>${{ number_format($producto->total_ingreso, 2) }}</strong></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
