<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Prueba de Impresión - A5</title>
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
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            width: 148mm;
            min-height: 210mm;
            padding: 10mm;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .header h1 {
            font-size: 18pt;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 9pt;
            color: #666;
        }
        
        .info-section {
            margin-bottom: 15px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 10pt;
        }
        
        .info-label {
            font-weight: bold;
        }
        
        .test-section {
            border: 1px dashed #000;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
        }
        
        .test-section h2 {
            font-size: 14pt;
            margin-bottom: 10px;
            color: #0066cc;
        }
        
        .characters {
            font-family: monospace;
            font-size: 9pt;
            background: #f5f5f5;
            padding: 10px;
            margin: 10px 0;
            word-break: break-all;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #000;
            font-size: 9pt;
        }
        
        .success {
            color: #009900;
            font-weight: bold;
            font-size: 12pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PRUEBA DE IMPRESIÓN</h1>
        <p>Sistema TPV - Formato A5</p>
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Fecha:</span>
            <span>{{ $date }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Impresora:</span>
            <span>{{ $printerName }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Tipo:</span>
            <span>Impresora Normal (Inkjet/Láser)</span>
        </div>
        <div class="info-row">
            <span class="info-label">Formato:</span>
            <span>A5 (148mm x 210mm)</span>
        </div>
    </div>
    
    <div class="test-section">
        <h2>✓ IMPRESIÓN CORRECTA</h2>
        <p>Si puede leer este texto claramente,<br>la impresora está configurada correctamente</p>
    </div>
    
    <div class="characters">
        <strong>Caracteres de prueba:</strong><br>
        ABCDEFGHIJKLMNOPQRSTUVWXYZ<br>
        abcdefghijklmnopqrstuvwxyz<br>
        0123456789<br>
        !@#$%^&*()_+-=[]{}|;:'",.<>/?<br>
        áéíóúñÁÉÍÓÚÑ
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Tamaño de fuente:</span>
            <span>11pt</span>
        </div>
        <div class="info-row">
            <span class="info-label">Márgenes:</span>
            <span>10mm</span>
        </div>
        <div class="info-row">
            <span class="info-label">Orientación:</span>
            <span>Vertical (Portrait)</span>
        </div>
    </div>
    
    <div class="footer">
        <p class="success">¡LA IMPRESORA FUNCIONA CORRECTAMENTE!</p>
        <p>Puede usar esta impresora para tickets en formato A5</p>
    </div>
</body>
</html>
