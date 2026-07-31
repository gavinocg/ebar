@php
    $business = \App\Models\BusinessSetting::getSettings();
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ticket {{ $sale->ticket_number }}</title>
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
        @if($business->logo)
            <img src="{{ public_path('storage/' . $business->logo) }}" class="logo" alt="Logo">
        @endif
        <h1>{{ $business->business_name }}</h1>
        <p>RFC: {{ $business->rfc }}</p>
        <p>Tel: {{ $business->phone }}</p>
    </div>
    
    <hr class="separator">
    
    <div>Ticket: {{ $sale->ticket_number }}</div>
    <div>Fecha: {{ $sale->created_at->format('d/m/Y H:i:s') }}</div>
    
    <hr class="separator">
    
    @foreach($sale->items as $item)
        <div class="item">
            <div class="item-name">{{ $item->quantity }} x {{ $item->product_name }}</div>
            <div class="item-detail">P.U.: ${{ number_format($item->price, 2) }}</div>
            <div class="item-subtotal">${{ number_format($item->subtotal, 2) }}</div>
        </div>
    @endforeach
    
    <hr class="separator">
    
    <div class="totals">
        <div>
            <span>Subtotal:</span>
            <span>${{ number_format($sale->subtotal, 2) }}</span>
        </div>
        @if($business->charge_tax)
        <div>
            <span>IVA ({{ $business->tax_percentage }}%):</span>
            <span>${{ number_format($sale->tax, 2) }}</span>
        </div>
        @endif
        <div class="grand-total">
            <span>TOTAL:</span>
            <span>${{ number_format($sale->total, 2) }}</span>
        </div>
    </div>
    
    <hr class="separator">
    
    <div class="footer">
        {{ $business->ticket_message ?? '¡GRACIAS POR SU COMPRA!' }}
    </div>
</body>
</html>
