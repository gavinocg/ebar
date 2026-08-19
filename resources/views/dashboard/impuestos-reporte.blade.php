@extends('layouts.sidebar')

@section('title', 'Reporte de Impuestos (IVA)')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Reporte de Impuestos (IVA)</h4>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Fecha Inicio</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha Fin</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">% IVA Configurado</label>
                <input type="text" class="form-control" value="{{ $porcentajeImpuesto }}%" readonly>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="{{ route('reportes.exportar_xlsx', ['tipo' => 'impuestos', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> XLSX
                </a>
                <a href="{{ route('reportes.exportar_pdf', ['tipo' => 'impuestos', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-danger">
                    <i class="bi bi-file-earmark-pdf"></i> PDF
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6>Total Ventas</h6>
                <h2>{{ $totalVentas }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h6>Subtotal Bruto</h6>
                <h2>${{ number_format($totalSubtotal, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h6>Descuentos</h6>
                <h2>${{ number_format($totalDescuento, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6>Total IVA Cobrado</h6>
                <h2>${{ number_format($totalImpuesto, 2) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6>Base Imponible (Subtotal - Descuentos)</h6>
                <h2>${{ number_format($baseImponible, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h6>IVA Calculado ({{ $porcentajeImpuesto }}%)</h6>
                <h2>${{ number_format($impuestoCalculado, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white {{ $totalImpuesto >= $impuestoCalculado ? 'bg-success' : 'bg-danger' }}">
            <div class="card-body">
                <h6>Diferencia (Cobrado - Calculado)</h6>
                <h2>${{ number_format($totalImpuesto - $impuestoCalculado, 2) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">IVA por Metodo de Pago</h5>
    </div>
    <div class="card-body">
        @if($porMetodo->isEmpty())
            <p class="text-muted text-center">No hay ventas en el periodo seleccionado.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Metodo de Pago</th>
                            <th class="text-end">Ventas</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Descuento</th>
                            <th class="text-end">Base Imponible</th>
                            <th class="text-end">IVA Cobrado</th>
                            <th class="text-end">IVA Calculado</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($porMetodo as $m)
                        <tr>
                            <td>
                                <span class="badge bg-{{ $m['metodo'] === 'efectivo' ? 'success' : ($m['metodo'] === 'credito' ? 'warning' : 'info') }}">
                                    {{ ucfirst($m['metodo']) }}
                                </span>
                            </td>
                            <td class="text-end">{{ $m['ventas'] }}</td>
                            <td class="text-end">${{ number_format($m['subtotal'], 2) }}</td>
                            <td class="text-end">${{ number_format($m['descuento'], 2) }}</td>
                            <td class="text-end">${{ number_format($m['base_imponible'], 2) }}</td>
                            <td class="text-end">${{ number_format($m['impuesto'], 2) }}</td>
                            <td class="text-end">${{ number_format($m['impuesto_calculado'], 2) }}</td>
                            <td class="text-end"><strong>${{ number_format($m['total'], 2) }}</strong></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Detalle por Categoria</h5>
    </div>
    <div class="card-body">
        @if($detalle->isEmpty())
            <p class="text-muted text-center">No hay ventas en el periodo seleccionado.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th class="text-end">Total Vendido</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Descuento</th>
                            <th class="text-end">IVA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detalle as $d)
                        <tr>
                            <td>{{ $d->categoria_nombre }}</td>
                            <td class="text-end">{{ $d->total_vendido }}</td>
                            <td class="text-end">${{ number_format($d->total_subtotal, 2) }}</td>
                            <td class="text-end">${{ number_format($d->total_descuento, 2) }}</td>
                            <td class="text-end">${{ number_format($d->total_impuesto, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection