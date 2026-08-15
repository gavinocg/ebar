@extends('layouts.sidebar')

@section('title', 'Reporte por Metodo de Pago')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Reporte por Metodo de Pago</h4>
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
    @foreach($metodos as $metodo)
    <div class="col-md-3">
        <div class="card text-white {{ $metodo->metodo_pago === 'efectivo' ? 'bg-success' : ($metodo->metodo_pago === 'credito' ? 'bg-warning' : 'bg-info') }}">
            <div class="card-body">
                <h6>{{ ucfirst($metodo->metodo_pago) }}</h6>
                <h2>${{ number_format($metodo->total_ingreso, 2) }}</h2>
                <small>{{ number_format($metodo->total_ventas) }} ventas</small>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Detalle por metodo de pago</h5>
    </div>
    <div class="card-body">
        @if($metodos->isEmpty())
            <p class="text-muted text-center">No hay ventas en el periodo seleccionado.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Metodo de Pago</th>
                            <th class="text-end">Ventas</th>
                            <th class="text-end">Ingreso Total</th>
                            <th class="text-end">Promedio</th>
                            <th class="text-end">% del Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($metodos as $metodo)
                        <tr>
                            <td>
                                <span class="badge bg-{{ $metodo->metodo_pago === 'efectivo' ? 'success' : ($metodo->metodo_pago === 'credito' ? 'warning' : 'info') }}">
                                    {{ ucfirst($metodo->metodo_pago) }}
                                </span>
                            </td>
                            <td class="text-end">{{ number_format($metodo->total_ventas) }}</td>
                            <td class="text-end"><strong>${{ number_format($metodo->total_ingreso, 2) }}</strong></td>
                            <td class="text-end">${{ number_format($metodo->promedio_venta, 2) }}</td>
                            <td class="text-end">{{ $totalGeneral > 0 ? number_format(($metodo->total_ingreso / $totalGeneral) * 100, 1) : 0 }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
