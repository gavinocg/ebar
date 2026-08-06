@php
    $business = \App\Models\ConfiguracionNegocio::obtenerConfiguracion();
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comprobante {{ $sale->numero_comprobante }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            width: 210mm;
            padding: 20mm;
        }
        
        .center {
            text-align: center;
        }
        
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .header .logo {
            max-width: 200px;
            max-height: 100px;
            margin-bottom: 10px;
        }
        
        .header h1 {
            font-size: 18pt;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 11pt;
            margin: 2px 0;
        }
        
        .separator {
            border: none;
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        
        .item {
            margin: 8px 0;
        }
        
        .item-name {
            font-weight: bold;
        }
        
        .item-detail {
            padding-left: 20px;
            font-size: 11pt;
        }
        
        .item-subtotal {
            text-align: right;
            font-weight: bold;
        }
        
        .totals {
            margin-top: 5px;
        }
        
        .totals div {
            display: flex;
            justify-content: space-between;
        }
        
        .grand-total {
            font-size: 14pt;
            font-weight: bold;
        }
        
        .footer {
            text-align: center;
            margin-top: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($business->logotipo)
            <img src="{{ asset('storage/' . $business->logotipo) }}" class="logo" alt="Logotipo">
        @endif
        <h1>{{ $business->nombre_negocio }}</h1>
        <p>RFC: {{ $business->rfc }}</p>
        <p>Tel: {{ $business->telefono }}</p>
    </div>
    
    <hr class="separator">
    
    <div>Comprobante: {{ $sale->numero_comprobante }}</div>
    <div>Fecha: {{ $sale->created_at->format('d/m/Y H:i:s') }}</div>
    
    <hr class="separator">
    
    @foreach($sale->detalles as $item)
        <div class="item">
            <div class="item-name">{{ $item->cantidad }} x {{ $item->nombre_producto }}</div>
            <div class="item-detail">P.U.: ${{ number_format($item->precio, 2) }}</div>
            <div class="item-subtotal">${{ number_format($item->subtotal, 2) }}</div>
        </div>
    @endforeach
    
    <hr class="separator">
    
    <div class="totals">
        <div>
            <span>Subtotal:</span>
            <span>${{ number_format($sale->subtotal, 2) }}</span>
        </div>
        @if((float)$sale->descuento > 0)
        <div>
            <span>Descuento:</span>
            <span>-${{ number_format($sale->descuento, 2) }}</span>
        </div>
        @endif
        @if($sale->impuesto_habilitado)
        <div>
            <span>Impuesto ({{ $sale->porcentaje_impuesto }}%):</span>
            <span>${{ number_format($sale->impuesto, 2) }}</span>
        </div>
        @endif
        <div class="grand-total">
            <span>TOTAL:</span>
            <span>${{ number_format($sale->total, 2) }}</span>
        </div>
    </div>
    
    <hr class="separator">
    
    <div class="footer">
        {{ $business->mensaje_comprobante ?? '¡GRACIAS POR SU COMPRA!' }}
    </div>
</body>
</html>
