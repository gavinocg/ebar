@extends('layouts.sidebar')

@section('title', 'Detalle de turno')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Turno #{{ $turno->id }}</h4>
    <a href="{{ route('caja.reporte') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="row mb-3">
    <div class="col-md-3 col-6"><div class="card"><div class="card-body"><small class="text-muted">Cajero</small><div class="fw-semibold">{{ $turno->usuario?->nombre ?? '—' }}</div></div></div></div>
    <div class="col-md-3 col-6"><div class="card"><div class="card-body"><small class="text-muted">Sucursal</small><div class="fw-semibold">{{ $turno->sucursal?->nombre ?? '—' }}</div></div></div></div>
    <div class="col-md-3 col-6"><div class="card"><div class="card-body"><small class="text-muted">Apertura</small><div>{{ $turno->abierto_en->format('d/m/Y H:i') }}</div></div></div></div>
    <div class="col-md-3 col-6"><div class="card"><div class="card-body"><small class="text-muted">Cierre</small><div>{{ $turno->cerrado_en?->format('d/m/Y H:i') ?? '—' }}</div></div></div></div>
</div>

<div class="row mb-3">
    <div class="col-md-4 col-6"><div class="card bg-light"><div class="card-body text-center"><small class="text-muted">Entradas (fondo + ventas + entradas)</small><div class="fs-5 fw-bold text-success">${{ number_format($entradas + $ventasEfectivo, 2) }}</div></div></div></div>
    <div class="col-md-4 col-6"><div class="card bg-light"><div class="card-body text-center"><small class="text-muted">Salidas (retiros + gastos)</small><div class="fs-5 fw-bold text-danger">${{ number_format($salidas, 2) }}</div></div></div></div>
    <div class="col-md-4 col-12"><div class="card bg-light"><div class="card-body text-center"><small class="text-muted">Efectivo esperado</small><div class="fs-5 fw-bold">${{ number_format((float) $turno->efectivo_esperado, 2) }}</div><small class="{{ (float) $turno->diferencia != 0 ? 'text-danger' : 'text-success' }}">Contado ${{ number_format((float) $turno->efectivo_contado, 2) }} · Diferencia ${{ number_format((float) $turno->diferencia, 2) }}</small></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-header"><h5 class="mb-0">Ventas ({{ $turno->ventas->count() }})</h5></div>
    <div class="card-body">
        @if($turno->ventas->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th>Comprobante</th><th>Fecha</th><th>Pago</th><th class="text-end">Subtotal</th><th class="text-end">Impuesto</th><th class="text-end">Total</th></tr>
                    </thead>
                    <tbody>
                        @foreach($turno->ventas as $venta)
                            <tr>
                                <td>{{ $venta->numero_comprobante }}</td>
                                <td>{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                                <td><span class="badge bg-secondary">{{ $venta->metodo_pago }}</span></td>
                                <td class="text-end">${{ number_format((float) $venta->subtotal, 2) }}</td>
                                <td class="text-end">${{ number_format((float) $venta->impuesto, 2) }}</td>
                                <td class="text-end fw-semibold">${{ number_format((float) $venta->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3">Total</td>
                            <td class="text-end">${{ number_format((float) $turno->ventas->sum('subtotal'), 2) }}</td>
                            <td class="text-end">${{ number_format((float) $turno->ventas->sum('impuesto'), 2) }}</td>
                            <td class="text-end">${{ number_format((float) $turno->ventas->sum('total'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <p class="text-muted text-center">Sin ventas en este turno.</p>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Movimientos de efectivo</h5></div>
    <div class="card-body">
        @if($turno->movimientosEfectivo->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th>Fecha</th><th>Tipo</th><th class="text-end">Monto</th><th>Motivo</th></tr>
                    </thead>
                    <tbody>
                        @foreach($turno->movimientosEfectivo->sortBy('created_at') as $mov)
                            <tr>
                                <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                                <td><span class="badge bg-secondary">{{ $mov->tipo }}</span></td>
                                <td class="text-end {{ (float) $mov->monto < 0 ? 'text-danger' : 'text-success' }}">${{ number_format((float) $mov->monto, 2) }}</td>
                                <td>{{ $mov->motivo }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted text-center">Sin movimientos de efectivo.</p>
        @endif
    </div>
</div>
@endsection