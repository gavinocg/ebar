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
