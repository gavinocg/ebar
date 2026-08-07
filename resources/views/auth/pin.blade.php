<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar PIN - TPV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <main class="container" style="max-width: 420px">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center p-4">
                <i class="bi bi-lock-fill display-4 text-muted"></i>
                <h5 class="mt-2 mb-1">{{ $usuario->nombre }}</h5>
                <p class="text-muted small mb-3">Ingresa tu PIN de 4 dígitos.</p>

                @if($errors->any())
                    <div class="alert alert-danger py-2">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('inicio_sesion.pin.validar') }}" id="pinForm">
                    @csrf
                    <input type="hidden" name="pin" id="pinValue">
                    <div class="mb-3" id="pinDisplay">
                        <span class="fs-4 tracking-widest">- - - -</span>
                    </div>
                    <div class="row g-2" id="keypad"></div>
                </form>

                <div class="mt-3">
                    <a href="{{ route('inicio_sesion.cajero') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Cambiar correo
                    </a>
                </div>
            </div>
        </div>
    </main>

<script>
let pin = '';

function renderDisplay() {
    const dots = Math.min(pin.length, 4);
    document.querySelector('#pinDisplay span').textContent = Array(4)
        .fill('•')
        .map((d, i) => (i < dots ? '•' : '-'))
        .join(' ');
}

function agregar(digito) {
    if (pin.length < 4) {
        pin += digito;
        renderDisplay();
        if (pin.length === 4) {
            document.getElementById('pinValue').value = pin;
            document.getElementById('pinForm').submit();
        }
    }
}

function borrar() {
    pin = pin.slice(0, -1);
    renderDisplay();
}

const keypad = document.getElementById('keypad');
for (let i = 1; i <= 9; i++) {
    keypad.insertAdjacentHTML('beforeend', `
        <div class="col-4">
            <button type="button" class="btn btn-outline-secondary w-100 fs-5 py-2" onclick="agregar('${i}')">${i}</button>
        </div>`);
}
keypad.insertAdjacentHTML('beforeend', '<div class="col-4"></div>');
keypad.insertAdjacentHTML('beforeend', `
    <div class="col-4">
        <button type="button" class="btn btn-outline-secondary w-100 fs-5 py-2" onclick="agregar('0')">0</button>
    </div>`);
keypad.insertAdjacentHTML('beforeend', '<div class="col-4"></div>');
keypad.insertAdjacentHTML('beforeend', `
    <div class="col-4">
        <button type="button" class="btn btn-outline-danger w-100 fs-5 py-2" onclick="borrar()"><i class="bi bi-backspace"></i></button>
    </div>`);
</script>
</body>
</html>