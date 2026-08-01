@extends('layouts.sidebar')

@section('title', 'Reporte de Ventas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Reporte de Ventas</h4>
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
                <h2>{{ $totalSales }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6>Ingresos Totales</h6>
                <h2>${{ number_format($totalRevenue, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h6>Ticket Promedio</h6>
                <h2>${{ number_format($averageTicket, 2) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Ventas por Día</h5>
    </div>
    <div class="card-body">
        @if($salesByDay->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Ventas</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($salesByDay as $day)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}</td>
                                <td><span class="badge bg-info">{{ $day->count }}</span></td>
                                <td><strong>${{ number_format($day->total, 2) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted text-center">No hay ventas en este período</p>
        @endif
    </div>
</div>
@endsection
