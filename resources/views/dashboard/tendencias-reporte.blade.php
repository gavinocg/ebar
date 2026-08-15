@extends('layouts.sidebar')

@section('title', 'Tendencias Comparativas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Tendencias Comparativas</h4>
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
                <h6>Periodo Actual</h6>
                <h2>${{ number_format($totalActual, 2) }}</h2>
                <small>{{ $startDate }} al {{ $endDate }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-secondary">
            <div class="card-body">
                <h6>Periodo Anterior</h6>
                <h2>${{ number_format($totalAnterior, 2) }}</h2>
                <small>{{ $prevStart }} al {{ $prevEnd }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white {{ $variacion >= 0 ? 'bg-success' : 'bg-danger' }}">
            <div class="card-body">
                <h6>Variacion</h6>
                <h2>{{ $variacion >= 0 ? '+' : '' }}{{ number_format($variacion, 1) }}%</h2>
                <small>{{ $variacion >= 0 ? 'Incremento' : 'Decremento' }}</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ventas del Periodo Actual</h5>
            </div>
            <div class="card-body">
                @if($ventasActuales->isEmpty())
                    <p class="text-muted text-center">Sin datos.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th class="text-end">Ventas</th>
                                    <th class="text-end">Ingreso</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ventasActuales as $v)
                                <tr>
                                    <td>{{ $v->fecha }}</td>
                                    <td class="text-end">{{ $v->total_ventas }}</td>
                                    <td class="text-end"><strong>${{ number_format($v->total_ingreso, 2) }}</strong></td>
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
                <h5 class="mb-0">Ventas del Periodo Anterior</h5>
            </div>
            <div class="card-body">
                @if($ventasAnteriores->isEmpty())
                    <p class="text-muted text-center">Sin datos.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th class="text-end">Ventas</th>
                                    <th class="text-end">Ingreso</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ventasAnteriores as $v)
                                <tr>
                                    <td>{{ $v->fecha }}</td>
                                    <td class="text-end">{{ $v->total_ventas }}</td>
                                    <td class="text-end"><strong>${{ number_format($v->total_ingreso, 2) }}</strong></td>
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
