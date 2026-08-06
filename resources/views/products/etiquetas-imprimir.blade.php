<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Etiquetas de precios</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; }
        .hoja { padding: 8px; }
        .etiquetas { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
        .etiqueta {
            border: 1px solid #333;
            border-radius: 4px;
            padding: 8px 10px;
            text-align: center;
            page-break-inside: avoid;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 90px;
        }
        .etiqueta .nombre { font-weight: bold; font-size: 13px; color: #111; margin-bottom: 4px; }
        .etiqueta .precio { font-size: 20px; font-weight: 800; color: #b91c1c; }
        .etiqueta .codigo { font-size: 9px; color: #666; margin-top: 2px; }
        @media print {
            body { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="hoja">
        <div class="etiquetas">
            @foreach($productos as $producto)
                @for($i = 0; $i < $copias; $i++)
                    <div class="etiqueta">
                        <div class="nombre">{{ $producto->nombre }}</div>
                        <div class="precio">${{ number_format($producto->precio, 2) }}</div>
                        @if($producto->codigo_barras)
                            <div class="codigo">{{ $producto->codigo_barras }}</div>
                        @endif
                        @if($producto->categoria)
                            <div class="codigo">{{ $producto->categoria->nombre }}</div>
                        @endif
                    </div>
                @endfor
            @endforeach
        </div>
    </div>
    <script>window.print();</script>
</body>
</html>