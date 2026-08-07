@if($ventas->isEmpty())
    <p class="text-muted text-center py-4">Aún no hay ventas registradas hoy.</p>
@else
    <table class="table table-sm table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Comprobante</th>
                <th>Método</th>
                <th class="text-end">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventas as $venta)
                <tr>
                    <td>{{ $venta->numero_comprobante }}</td>
                    <td>
                        @if($venta->metodo_pago === 'efectivo')
                            <span class="badge bg-success">Efectivo</span>
                        @elseif($venta->metodo_pago === 'credito')
                            <span class="badge bg-warning text-dark">Crédito</span>
                        @else
                            <span class="badge bg-info">Transferencia</span>
                        @endif
                    </td>
                    <td class="text-end">${{ number_format($venta->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="2" class="text-end fw-bold">TOTAL:</td>
                <td class="text-end fw-bold text-primary">${{ number_format($total, 2) }}</td>
            </tr>
        </tfoot>
    </table>
@endif