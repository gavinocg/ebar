@extends('layouts.pos')

@section('title', 'Cierre de caja')

@section('content')
<div class="container" style="max-width: 560px;">
    <h5 class="mb-3"><i class="bi bi-cash-stack"></i> Cierre de caja</h5>
    <p class="text-muted small">{{ auth()->user()->nombre }} — Cuadre del turno activo.</p>

    @if($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <div class="alert alert-info py-2">
        <div class="d-flex justify-content-between">
            <span>Efectivo esperado (sistema):</span>
            <strong>${{ number_format($esperado, 2) }}</strong>
        </div>
    </div>

    @if($comprobantesNoEfectivo->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header bg-warning py-2"><strong><i class="bi bi-receipt"></i> Comprobantes cobrados sin efectivo</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Comprobante</th>
                            <th>Método</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comprobantesNoEfectivo as $venta)
                            <tr>
                                <td>{{ $venta->numero_comprobante }}</td>
                                <td>{{ $venta->metodo_pago === 'credito' ? 'Crédito' : 'Transferencia' }}</td>
                                <td class="text-end">${{ number_format($venta->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('caja.cerrar') }}" id="cierreForm">
        @csrf

        <div class="card mb-3">
            <div class="card-header py-2"><strong>Tipo de cierre</strong></div>
            <div class="card-body">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="es_final" id="cierre_temporal" value="0" checked onchange="toggleCuadre()">
                    <label class="form-check-label" for="cierre_temporal">Cierre temporal (puedo reabrir después)</label>
                </div>
                <div class="form-check mb-0">
                    <input class="form-check-input" type="radio" name="es_final" id="cierre_final" value="1" onchange="toggleCuadre()">
                    <label class="form-check-label" for="cierre_final">Cierre final del día (con cuadre)</label>
                </div>
            </div>
        </div>

        <div id="seccionCuadre" style="display:none">
        @if($cuadreActivo)
            <div class="card mb-3">
                <div class="card-header py-2"><strong><i class="bi bi-bank"></i> Billetes</strong></div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach([100, 50, 20, 10, 5, 1] as $den)
                            <div class="col-6">
                                <label class="form-label small mb-1">${{ $den }}</label>
                                <input type="number" class="form-control form-control-sm denom-input" data-denom="{{ $den }}" name="billetes[{{ $den }}]" min="0" value="0" step="1">
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 text-end">
                        <small class="text-muted">Total billetes: <strong id="totalBilletes">$0.00</strong></small>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header py-2"><strong><i class="bi bi-coin"></i> Monedas</strong></div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach([1, 0.50, 0.25, 0.10, 0.05, 0.01] as $den)
                            <div class="col-6">
                                <label class="form-label small mb-1">${{ number_format($den, 2) }}</label>
                                <input type="number" class="form-control form-control-sm denom-input-moneda" data-denom="{{ $den }}" name="monedas[{{ $den }}]" min="0" value="0" step="1">
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 text-end">
                        <small class="text-muted">Total monedas: <strong id="totalMonedas">$0.00</strong></small>
                    </div>
                </div>
            </div>

            <div class="card mb-3 border-primary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <span class="fw-bold">TOTAL CONTADO:</span>
                    <span class="fs-4 fw-bold text-primary" id="totalContado">$0.00</span>
                </div>
            </div>
        @else
            <div class="alert alert-warning py-2">
                <i class="bi bi-info-circle"></i> El cuadre con conteo de denominaciones está desactivado para este cajero. El cierre final se registrará sin desglose.
            </div>
        @endif
        </div>

        <div class="mb-3">
            <label class="form-label small" for="notas">Notas (opcional)</label>
            <textarea id="notas" name="notas" class="form-control form-control-sm" rows="2" maxlength="1000" placeholder="Observaciones del cierre"></textarea>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('punto_venta.inicio') }}" class="btn btn-outline-secondary flex-fill">Cancelar</a>
            <button type="submit" class="btn btn-danger flex-fill"><i class="bi bi-lock"></i> Cerrar caja</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function calcular() {
    let totalBilletes = 0;
    document.querySelectorAll('.denom-input').forEach(input => {
        const denom = parseFloat(input.dataset.denom);
        const cant = parseInt(input.value) || 0;
        totalBilletes += denom * cant;
    });
    let totalMonedas = 0;
    document.querySelectorAll('.denom-input-moneda').forEach(input => {
        const denom = parseFloat(input.dataset.denom);
        const cant = parseInt(input.value) || 0;
        totalMonedas += denom * cant;
    });
    const total = totalBilletes + totalMonedas;
    document.getElementById('totalBilletes').textContent = '$' + totalBilletes.toFixed(2);
    document.getElementById('totalMonedas').textContent = '$' + totalMonedas.toFixed(2);
    document.getElementById('totalContado').textContent = '$' + total.toFixed(2);
}

function toggleCuadre() {
    const esFinal = document.getElementById('cierre_final').checked;
    document.getElementById('seccionCuadre').style.display = esFinal ? '' : 'none';
}

document.querySelectorAll('.denom-input, .denom-input-moneda').forEach(input => {
    input.addEventListener('input', calcular);
});
toggleCuadre();
calcular();
</script>
@endpush
@endsection