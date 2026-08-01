@extends('layouts.app')

@section('title', 'Comprobante ' . $sale->numero_comprobante)

@section('content')
<div class="mb-3">
    <a href="{{ route('ventas.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <button class="btn btn-primary" onclick="window.print()">
        <i class="bi bi-printer"></i> Imprimir
    </button>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Comprobante {{ $sale->numero_comprobante }}</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Fecha:</strong> {{ $sale->created_at->format('d/m/Y H:i:s') }}</p>
                <p><strong>Método de pago:</strong> 
                    @if($sale->metodo_pago == 'efectivo')
                        Efectivo
                    @elseif($sale->metodo_pago == 'tarjeta')
                        Tarjeta
                    @else
                        Transferencia
                    @endif
                </p>
            </div>
            <div class="col-md-6 text-end">
                <h3 class="text-success">${{ number_format($sale->total, 2) }}</h3>
                <small class="text-muted">Total</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-end">P. Unitario</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->detalles as $item)
                        <tr>
                            <td>{{ $item->nombre_producto }}</td>
                            <td class="text-center">{{ $item->cantidad }}</td>
                            <td class="text-end">${{ number_format($item->precio, 2) }}</td>
                            <td class="text-end">${{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                        <td class="text-end">${{ number_format($sale->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end"><strong>{{ $sale->impuesto_habilitado ? 'Impuesto (' . number_format($sale->porcentaje_impuesto, 2) . '%):' : 'Impuestos:' }}</strong></td>
                        <td class="text-end">${{ number_format($sale->impuesto, 2) }}</td>
                    </tr>
                    <tr class="table-success">
                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                        <td class="text-end"><strong>${{ number_format($sale->total, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end">Pagado:</td>
                        <td class="text-end">${{ number_format($sale->pagado, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end">Cambio:</td>
                        <td class="text-end">${{ number_format($sale->cambio, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($sale->notes)
            <div class="mt-3">
                <strong>Notas:</strong>
                <p>{{ $sale->notes }}</p>
            </div>
        @endif
    </div>
</div>
@endsection
