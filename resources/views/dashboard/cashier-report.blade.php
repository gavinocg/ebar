@extends('layouts.sidebar')

@section('title', 'Ventas por Cajero')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Ventas por Cajero</h4>
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
    <div class="col-md-6">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6>Ventas totales</h6>
                <h2>{{ $granTotalVentas }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6>Ingresos totales</h6>
                <h2>${{ number_format($granTotalIngresos, 2) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Desempeño por cajero</h5>
    </div>
    <div class="card-body">
        @if($porCajero->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Cajero</th>
                            <th class="text-end">Ventas</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Impuesto</th>
                            <th class="text-end">Ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($porCajero as $fila)
                            <tr>
                                <td class="fw-semibold">{{ $fila->nombre }}</td>
                                <td class="text-end"><span class="badge bg-info">{{ $fila->total_ventas }}</span></td>
                                <td class="text-end">${{ number_format($fila->total_subtotal, 2) }}</td>
                                <td class="text-end">${{ number_format($fila->total_impuesto, 2) }}</td>
                                <td class="text-end"><strong>${{ number_format($fila->total_ingresos, 2) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted text-center">No hay ventas registradas para este cajero en el período.</p>
        @endif
    </div>
</div>
@endsection