@extends('layouts.sidebar')

@section('title', 'Comprobante ' . $sale->numero_comprobante)

@section('content')
<div class="mb-3">
    <a href="{{ route('ventas.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <button class="btn btn-primary" onclick="window.print()">
        <i class="bi bi-printer"></i> Imprimir
    </button>
    @can('reembolsar', $sale)
    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reembolsoModal">
        <i class="bi bi-arrow-counterclockwise"></i> Reembolsar / Devolver
    </button>
    @endcan
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
                    @if($sale->metodo_pago === 'credito')
                        <tr class="table-warning">
                            <td colspan="3" class="text-end">Pendiente por cobrar:</td>
                            <td class="text-end"><strong>${{ number_format($sale->total - $sale->pagado, 2) }}</strong></td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="3" class="text-end">Pagado:</td>
                            <td class="text-end">${{ number_format($sale->pagado, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end">Cambio:</td>
                            <td class="text-end">${{ number_format($sale->cambio, 2) }}</td>
                        </tr>
                    @endif
                </tfoot>
            </table>
        </div>

        @if($sale->notas)
            <div class="mt-3">
                <strong>Notas:</strong>
                <p>{{ $sale->notas }}</p>
            </div>
        @endif

        @if($sale->reembolsos->isNotEmpty())
            <div class="mt-4">
                <h6>Reembolsos de esta venta</h6>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th class="text-end">Monto</th>
                            <th>Motivo</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->reembolsos as $reembolso)
                            <tr>
                                <td>{{ $reembolso->tipo === 'total' ? 'Total' : 'Parcial' }}</td>
                                <td class="text-end">${{ number_format($reembolso->monto, 2) }}</td>
                                <td>{{ $reembolso->motivo }}</td>
                                <td>{{ $reembolso->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@can('reembolsar', $sale)
<div class="modal fade" id="reembolsoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('reembolsos.crear', $sale) }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Reembolsar / Devolver</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label">Tipo de reembolso</label>
                            <select name="tipo" id="tipoReembolso" class="form-select" onchange="alternarReembolsoParcial()">
                                <option value="total">Total</option>
                                <option value="parcial">Parcial</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Método</label>
                            <select name="metodo" class="form-select">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="credito">Crédito</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Motivo (obligatorio)</label>
                        <textarea name="motivo" class="form-control" rows="2" maxlength="500" required placeholder="Motivo de la devolución"></textarea>
                    </div>

                    <div id="itemsParcial" style="display:none">
                        <h6 class="mb-2">Artículos a devolver (cantidad)</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center">Vendidos</th>
                                        <th class="text-center">Devueltos</th>
                                        <th class="text-center" style="width:120px">A devolver</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sale->detalles as $detalle)
                                        @php
                                            $devuelto = $sale->reembolsos->flatMap->detalles->where('detalle_venta_id', $detalle->id)->sum('cantidad');
                                            $disponible = max(0, $detalle->cantidad - $devuelto);
                                        @endphp
                                        <tr>
                                            <td>{{ $detalle->nombre_producto }}</td>
                                            <td class="text-center">{{ $detalle->cantidad }}</td>
                                            <td class="text-center">{{ $devuelto }}</td>
                                            <td class="text-center">
                                                <input type="number" name="items[{{ $detalle->id }}]" class="form-control form-control-sm" min="0" max="{{ $disponible }}" value="0" {{ $disponible < 1 ? 'disabled' : '' }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Registrar reembolso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function alternarReembolsoParcial() {
    const tipo = document.getElementById('tipoReembolso').value;
    const inputs = document.querySelectorAll('#itemsParcial input[name^="items"]');
    inputs.forEach(input => {
        if (tipo === 'parcial') {
            input.value = input.dataset.max ? input.dataset.max : input.getAttribute('max');
        } else {
            input.value = 0;
        }
    });
    document.getElementById('itemsParcial').style.display = tipo === 'parcial' ? '' : 'none';
}
</script>
@endcan
@endsection
