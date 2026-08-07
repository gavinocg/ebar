<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso al TPV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <main class="container" style="max-width: 420px">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h4 mb-4">Acceso al TPV</h1>
                <form method="POST" action="{{ route('inicio_sesion.guardar') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="correo">Correo electrónico</label>
                        <input class="form-control @error('correo') is-invalid @enderror" id="correo" name="correo" type="email" value="{{ old('correo') }}" required autofocus>
                        @error('correo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña</label>
                        <input class="form-control" id="password" name="password" type="password" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
                        <label class="form-check-label" for="remember">Recordarme</label>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Entrar</button>
                </form>
                <div class="text-center mt-3 border-top pt-3">
                    <span class="text-muted small">¿Eres cajero?</span>
                    <a href="{{ route('inicio_sesion.cajero') }}" class="btn btn-outline-primary btn-sm ms-2">
                        <i class="bi bi-person-badge"></i> Soy Cajero
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
