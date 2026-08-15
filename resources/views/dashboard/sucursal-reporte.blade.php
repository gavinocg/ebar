@extends('layouts.sidebar')

@section('title', 'Reporte por Sucursal y Cajero')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Reporte por Sucursal y Cajero</h4>
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

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-building"></i> Por Sucursal</h5>
            </div>
            <div class="card-body">
                @if($sucursales->isEmpty())
                    <p class="text-muted text-center">Sin datos.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Sucursal</th>
                                    <th class="text-end">Ventas</th>
                                    <th class="text-end">Ingreso</th>
                                    <th class="text-end">Promedio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sucursales as $s)
                                <tr>
                                    <td class="fw-semibold">{{ $s->sucursal_nombre }}</td>
                                    <td class="text-end"><span class="badge bg-info">{{ number_format($s->total_ventas) }}</span></td>
                                    <td class="text-end"><strong>${{ number_format($s->total_ingreso, 2) }}</strong></td>
                                    <td class="text-end">${{ number_format($s->promedio_venta, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person"></i> Por Cajero</h5>
            </div>
            <div class="card-body">
                @if($cajeros->isEmpty())
                    <p class="text-muted text-center">Sin datos.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Cajero</th>
                                    <th class="text-end">Ventas</th>
                                    <th class="text-end">Ingreso</th>
                                    <th class="text-end">Promedio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cajeros as $c)
                                <tr>
                                    <td class="fw-semibold">{{ $c->cajero_nombre }}</td>
                                    <td class="text-end"><span class="badge bg-info">{{ number_format($c->total_ventas) }}</span></td>
                                    <td class="text-end"><strong>${{ number_format($c->total_ingreso, 2) }}</strong></td>
                                    <td class="text-end">${{ number_format($c->promedio_venta, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
