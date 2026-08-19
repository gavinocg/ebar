<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cambiar contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <main class="container" style="max-width: 420px">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <i class="bi bi-key-fill display-4 text-primary"></i>
                    <h1 class="h4 mt-2 mb-1">Cambio de contraseña requerido</h1>
                    <p class="text-muted small mb-0">Por seguridad debes establecer una nueva contraseña antes de continuar.</p>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.cambiar.guardar') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="password_actual">Contraseña actual</label>
                        <input class="form-control @error('password_actual') is-invalid @enderror" id="password_actual" name="password_actual" type="password" required autofocus>
                        @error('password_actual')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Nueva contraseña</label>
                        <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" required minlength="8">
                        <div class="form-text">Mínimo 8 caracteres.</div>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password_confirmation">Confirmar nueva contraseña</label>
                        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Guardar contraseña</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>