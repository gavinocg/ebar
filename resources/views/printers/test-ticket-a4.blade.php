<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Prueba de Impresión - {{ $paperSize }}</title>
    <style>
        @page {
            size: {{ $paperSize }} portrait;
            margin: 15mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            padding: 15mm;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px double #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .header h1 {
            font-size: 22pt;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 10pt;
            color: #666;
        }
        
        .info-section {
            margin-bottom: 25px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 8px;
            background: #f9f9f9;
            border-radius: 3px;
        }
        
        .info-label {
            font-weight: bold;
            color: #333;
        }
        
        .test-section {
            border: 2px dashed #2c3e50;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
            background: #ecf0f1;
            border-radius: 10px;
        }
        
        .test-section h2 {
            font-size: 18pt;
            margin-bottom: 15px;
            color: #27ae60;
        }
        
        .test-section p {
            font-size: 12pt;
            color: #555;
        }
        
        .characters {
            font-family: 'Courier New', monospace;
            font-size: 10pt;
            background: #f5f5f5;
            padding: 15px;
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .characters div {
            margin-bottom: 8px;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 3px double #2c3e50;
        }
        
        .success {
            color: #27ae60;
            font-weight: bold;
            font-size: 16pt;
            margin-bottom: 10px;
        }
        
        .info-box {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PRUEBA DE IMPRESIÓN</h1>
        <p>Sistema TPV - Formato {{ $paperSize }}</p>
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Fecha y Hora:</span>
            <span>{{ $date }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Nombre de Impresora:</span>
            <span>{{ $printerName }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Tipo de Impresora:</span>
            <span>Impresora Normal (Inkjet/Láser)</span>
        </div>
        <div class="info-row">
            <span class="info-label">Formato de Papel:</span>
            <span>{{ $paperSize }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Configuración:</span>
            <span>Vertical (Portrait) - Márgenes 15mm</span>
        </div>
    </div>
    
    <div class="test-section">
        <h2>✓ IMPRESIÓN CORRECTA</h2>
        <p>Si puede leer este texto claramente,<br>la impresora está configurada correctamente</p>
    </div>
    
    <div class="characters">
        <div><strong>Caracteres de prueba:</strong></div>
        <div>ABCDEFGHIJKLMNOPQRSTUVWXYZ</div>
        <div>abcdefghijklmnopqrstuvwxyz</div>
        <div>0123456789</div>
        <div>!@#$%^&*()_+-=[]{}|;:'",.<>/?</div>
        <div>áéíóúñÁÉÍÓÚÑ üÜ</div>
    </div>
    
    <div class="info-box">
        <strong>Especificaciones técnicas:</strong><br>
        • Tamaño de fuente: 12pt<br>
        • Márgenes: 15mm en todos los lados<br>
        • Orientación: Vertical (Portrait)<br>
        • Interlineado: 1.6
    </div>
    
    <div class="footer">
        <p class="success">¡LA IMPRESORA FUNCIONA CORRECTAMENTE!</p>
        <p>Puede usar esta impresora para tickets en formato {{ $paperSize }}</p>
    </div>
</body>
</html>
