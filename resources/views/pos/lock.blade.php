@extends('layouts.sidebar')

@section('title', 'Desbloquear POS')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="card shadow-sm" style="width: 340px;">
        <div class="card-body text-center">
            <i class="bi bi-lock-fill display-4 text-muted"></i>
            <h5 class="mt-2 mb-1">{{ auth()->user()->nombre }}</h5>
            <p class="text-muted small mb-3">Ingresa tu PIN para continuar vendiendo.</p>

            @if($errors->any())
                <div class="alert alert-danger py-2">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('punto_venta.desbloquear') }}" id="pinForm">
                @csrf
                <input type="hidden" name="pin" id="pinValue">
                <div class="mb-3" id="pinDisplay">
                    <span class="fs-4 tracking-widest">- - - -</span>
                </div>
                <div class="row g-2" id="keypad"></div>
            </form>

            <div class="mt-3 d-flex justify-content-between">
                <form method="POST" action="{{ route('cerrar_sesion') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </button>
                </form>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="borrar()">
                    <i class="bi bi-backspace"></i> Borrar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
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
</script>
@endpush
@endsection