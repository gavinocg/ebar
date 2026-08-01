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
            size: A5 portrait;
            margin: 10mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
            width: 148mm;
            padding: 10mm;
        }
        
        .center {
            text-align: center;
        }
        
        .header {
            text-align: center;
            margin-bottom: 8px;
        }
        
        .header .logo {
            max-width: 130px;
            max-height: 65px;
            margin-bottom: 8px;
        }
        
        .header h1 {
            font-size: 14pt;
            margin-bottom: 3px;
        }
        
        .header p {
            font-size: 9pt;
            margin: 1px 0;
        }
        
        .separator {
            border: none;
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        
        .item {
            margin: 6px 0;
        }
        
        .item-name {
            font-weight: bold;
            font-size: 10pt;
        }
        
        .item-detail {
            padding-left: 15px;
            font-size: 9pt;
        }
        
        .item-subtotal {
            text-align: right;
            font-weight: bold;
            font-size: 10pt;
        }
        
        .totals {
            margin-top: 5px;
        }
        
        .totals div {
            display: flex;
            justify-content: space-between;
        }
        
        .grand-total {
            font-size: 12pt;
            font-weight: bold;
        }
        
        .footer {
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
            font-size: 10pt;
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
