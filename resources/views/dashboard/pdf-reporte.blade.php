<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .header .periodo {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .summary-cards {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .summary-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px 15px;
            min-width: 150px;
            margin: 5px;
            text-align: center;
        }
        .summary-card h4 {
            margin: 0 0 5px 0;
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
        }
        .summary-card .value {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 4px 6px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
            color: white;
        }
        .bg-success { background-color: #28a745; }
        .bg-warning { background-color: #ffc107; color: #333; }
        .bg-info { background-color: #17a2b8; }
        .bg-danger { background-color: #dc3545; }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #999;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 15px 0 10px 0;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <div class="periodo">{{ $startDate }} - {{ $endDate }}</div>
    </div>

    @if($porcentajeImpuesto !== null && $title === 'Reporte de Impuestos (IVA)')
        <div class="section-title">Resumen General</div>
        <div class="summary-cards">
            <div class="summary-card">
                <h4>Total Ventas</h4>
                <div class="value">{{ $totalVentas ?? $rows->count() }}</div>
            </div>
            <div class="summary-card">
                <h4>Subtotal Bruto</h4>
                <div class="value">${{ number_format($totalSubtotal ?? $rows->sum('subtotal'), 2) }}</div>
            </div>
            <div class="summary-card">
                <h4>Descuentos</h4>
                <div class="value">${{ number_format($totalDescuento ?? $rows->sum('descuento'), 2) }}</div>
            </div>
            <div class="summary-card">
                <h4>IVA Cobrado</h4>
                <div class="value">${{ number_format($totalImpuesto ?? $rows->sum('impuesto'), 2) }}</div>
            </div>
            <div class="summary-card">
                <h4>Total</h4>
                <div class="value">${{ number_format($totalTotal ?? $rows->sum('total'), 2) }}</div>
            </div>
        </div>

        @if(isset($baseImponible))
        <div class="summary-cards">
            <div class="summary-card">
                <h4>Base Imponible</h4>
                <div class="value">${{ number_format($baseImponible, 2) }}</div>
            </div>
            <div class="summary-card">
                <h4>IVA Calculado ({{ $porcentajeImpuesto }}%)</h4>
                <div class="value">${{ number_format($impuestoCalculado, 2) }}</div>
            </div>
            <div class="summary-card">
                <h4>Diferencia</h4>
                <div class="value" style="color: {{ $totalImpuesto >= $impuestoCalculado ? '#28a745' : '#dc3545' }}">${{ number_format($totalImpuesto - $impuestoCalculado, 2) }}</div>
            </div>
        </div>
        @endif
    @endif

    <div class="section-title">{{ $title }}</div>

    @if($rows->isEmpty())
        <p class="text-center" style="color: #999; padding: 20px;">No hay datos en el periodo seleccionado.</p>
    @else
        <table>
            <thead>
                @if($title === 'Reporte de Ventas')
                    <tr>
                        <th>Fecha</th>
                        <th>Comprobante</th>
                        <th>Método</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Desc.</th>
                        <th class="text-end">IVA</th>
                        <th class="text-end">Total</th>
                        <th>Cajero</th>
                        <th>Sucursal</th>
                    </tr>
                @elseif($title === 'Ranking de Productos')
                    <tr>
                        <th>Producto</th>
                        <th class="text-end">Total Vendido</th>
                        <th class="text-end">Total Ingreso</th>
                    </tr>
                @elseif($title === 'Ventas por Categoria')
                    <tr>
                        <th>Categoría</th>
                        <th class="text-end">Total Vendido</th>
                        <th class="text-end">Total Ingreso</th>
                    </tr>
                @elseif($title === 'Reporte por Metodo de Pago')
                    <tr>
                        <th>Método</th>
                        <th class="text-end">Ventas</th>
                        <th class="text-end">Ingreso Total</th>
                        <th class="text-end">Promedio</th>
                    </tr>
                @elseif($title === 'Tendencias Comparativas')
                    <tr>
                        <th>Fecha</th>
                        <th class="text-end">Ventas</th>
                        <th class="text-end">Ingreso Total</th>
                    </tr>
                @elseif($title === 'Reporte por Sucursal')
                    <tr>
                        <th>Sucursal</th>
                        <th class="text-end">Ventas</th>
                        <th class="text-end">Ingreso Total</th>
                        <th class="text-end">Promedio</th>
                    </tr>
                @elseif($title === 'Reporte de Impuestos (IVA)')
                    <tr>
                        <th>Método</th>
                        <th class="text-end">Ventas</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Descuento</th>
                        <th class="text-end">Base</th>
                        <th class="text-end">IVA</th>
                        <th class="text-end">Calculado</th>
                        <th class="text-end">Total</th>
                    </tr>
                @endif
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        @if($title === 'Reporte de Ventas')
                            <td>{{ $row['fecha'] }}</td>
                            <td>{{ $row['comprobante'] }}</td>
                            <td><span class="badge bg-{{ $row['metodo'] === 'efectivo' ? 'success' : ($row['metodo'] === 'credito' ? 'warning' : 'info') }}">{{ ucfirst($row['metodo']) }}</span></td>
                            <td class="text-end">{{ $row['subtotal'] }}</td>
                            <td class="text-end">{{ $row['descuento'] }}</td>
                            <td class="text-end">{{ $row['impuesto'] }}</td>
                            <td class="text-end"><strong>{{ $row['total'] }}</strong></td>
                            <td>{{ $row['cajero'] }}</td>
                            <td>{{ $row['sucursal'] }}</td>
                        @elseif($title === 'Ranking de Productos')
                            <td>{{ $row['nombre'] }}</td>
                            <td class="text-end">{{ $row['total_vendido'] }}</td>
                            <td class="text-end">{{ $row['total_ingreso'] }}</td>
                        @elseif($title === 'Ventas por Categoria')
                            <td>{{ $row['categoria'] }}</td>
                            <td class="text-end">{{ $row['total_vendido'] }}</td>
                            <td class="text-end">{{ $row['total_ingreso'] }}</td>
                        @elseif($title === 'Reporte por Metodo de Pago')
                            <td><span class="badge bg-{{ $row['metodo'] === 'efectivo' ? 'success' : ($row['metodo'] === 'credito' ? 'warning' : 'info') }}">{{ ucfirst($row['metodo']) }}</span></td>
                            <td class="text-end">{{ $row['ventas'] }}</td>
                            <td class="text-end">{{ $row['ingreso'] }}</td>
                            <td class="text-end">{{ $row['promedio'] }}</td>
                        @elseif($title === 'Tendencias Comparativas')
                            <td>{{ $row['fecha'] }}</td>
                            <td class="text-end">{{ $row['ventas'] }}</td>
                            <td class="text-end">{{ $row['ingreso'] }}</td>
                        @elseif($title === 'Reporte por Sucursal')
                            <td>{{ $row['sucursal'] }}</td>
                            <td class="text-end">{{ $row['ventas'] }}</td>
                            <td class="text-end">{{ $row['ingreso'] }}</td>
                            <td class="text-end">{{ $row['promedio'] }}</td>
                        @elseif($title === 'Reporte de Impuestos (IVA)')
                            <td><span class="badge bg-{{ $row['metodo'] === 'efectivo' ? 'success' : ($row['metodo'] === 'credito' ? 'warning' : 'info') }}">{{ ucfirst($row['metodo']) }}</span></td>
                            <td class="text-end">{{ $row['ventas'] }}</td>
                            <td class="text-end">{{ $row['subtotal'] }}</td>
                            <td class="text-end">{{ $row['descuento'] }}</td>
                            <td class="text-end">{{ $row['base'] }}</td>
                            <td class="text-end">{{ $row['impuesto'] }}</td>
                            <td class="text-end">{{ $row['calculado'] }}</td>
                            <td class="text-end"><strong>{{ $row['total'] }}</strong></td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i:s') }} | e-Bar TPV Ecuador
    </div>
</body>
</html>