<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Credenciales de acceso</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f4f6f8; margin: 0; padding: 24px; }
        .contenedor { max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; }
        .cabecera { background: #0d6efd; color: #ffffff; padding: 20px 24px; }
        .cabecera h1 { margin: 0; font-size: 18px; }
        .cuerpo { padding: 24px; color: #212529; }
        .datos { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 16px; margin: 16px 0; }
        .datos p { margin: 6px 0; }
        .clave { font-family: monospace; font-size: 16px; font-weight: bold; }
        .aviso { font-size: 13px; color: #6c757d; }
    </style>
</head>
<body>
<div class="contenedor">
    <div class="cabecera">
        <h1>¡Bienvenido, {{ $nombre }}!</h1>
    </div>
    <div class="cuerpo">
        <p>Se creó tu bar <strong>{{ $nombreBar }}</strong> en {{ config('app.name') }}. Estas son tus credenciales de primer ingreso:</p>

        <div class="datos">
            <p>Correo: <strong>{{ $correo }}</strong></p>
            <p>Clave temporal: <span class="clave">{{ $clave }}</span></p>
        </div>

        <p>Ingresa en <a href="{{ $url }}">{{ $url }}</a> con estas credenciales. En tu primer ingreso deberás cambiar la contraseña.</p>

        <p class="aviso">No compartas estas credenciales con nadie.</p>
    </div>
</div>
</body>
</html>