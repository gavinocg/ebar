<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso Cajero - TPV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <main class="container" style="max-width: 420px">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h4 mb-0">Acceso Cajero</h1>
                    <a href="{{ route('inicio_sesion') }}" class="small text-muted"><i class="bi bi-arrow-left"></i> Volver</a>
                </div>
                <p class="text-muted small mb-3">Ingresa tu correo para acceder con tu PIN.</p>

                <form method="POST" action="{{ route('inicio_sesion.cajero.buscar') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="correo">Correo electrónico</label>
                        <input class="form-control @error('correo') is-invalid @enderror" id="correo" name="correo" type="email" value="{{ old('correo') }}" required autofocus>
                        @error('correo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Continuar</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>