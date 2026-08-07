@php($business = \App\Models\ConfiguracionNegocio::obtenerConfiguracion())
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante {{ $sale->numero_comprobante }}</title>
    <style>
        @page { size: 58mm auto; margin: 0; }
        body { width: 58mm; margin: 0; padding: 3mm; box-sizing: border-box; font: 11px/1.15 monospace; }
        .centro { text-align: center; }
        .grande { font-size: 14px; font-weight: 700; }
        .linea { display: flex; justify-content: space-between; gap: 4px; white-space: nowrap; }
        .linea span:first-child { overflow: hidden; text-overflow: ellipsis; }
        .total { font-size: 13px; font-weight: 700; }
        hr { border: 0; border-top: 1px dashed #000; margin: 5px 0; }
    </style>
</head>
<body>
    <div class="centro grande">{{ $business->nombre_negocio }}</div>
    <div class="centro">{{ $sale->created_at->format('d/m/Y H:i') }}</div>
    <hr>
    @foreach($sale->detalles as $item)
        <div class="linea">
            <span>{{ $item->cantidad }}x{{ $item->nombre_producto }}</span>
            <span>${{ number_format($item->subtotal, 2) }}</span>
        </div>
    @endforeach
    <hr>
    <div class="linea total"><span>Total</span><span>${{ number_format($sale->total, 2) }}</span></div>
    <div class="linea"><span>Pago</span><span>${{ number_format($sale->pagado, 2) }}</span></div>
    <div class="linea"><span>Cambio</span><span>${{ number_format($sale->cambio, 2) }}</span></div>
    @if($business->mensaje_comprobante)
        <p class="centro">{{ $business->mensaje_comprobante }}</p>
    @endif
</body>
</html>
