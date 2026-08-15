@extends('layouts.sidebar')

@section('title', 'Ventas por Categoria')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Ventas por Categoria</h4>
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

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Desempeno por categoria</h5>
    </div>
    <div class="card-body">
        @if($categorias->isEmpty())
            <p class="text-muted text-center">No hay ventas en el periodo seleccionado.</p>
        @else
            @php $totalGeneral = $categorias->sum('total_ingreso'); @endphp
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Categoria</th>
                            <th class="text-end">Unidades Vendidas</th>
                            <th class="text-end">Ingreso Total</th>
                            <th class="text-end">% del Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categorias as $i => $cat)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $cat->categoria_nombre }}</td>
                            <td class="text-end"><span class="badge bg-info">{{ number_format($cat->total_vendido) }}</span></td>
                            <td class="text-end"><strong>${{ number_format($cat->total_ingreso, 2) }}</strong></td>
                            <td class="text-end">{{ $totalGeneral > 0 ? number_format(($cat->total_ingreso / $totalGeneral) * 100, 1) : 0 }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
