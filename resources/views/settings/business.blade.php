@extends('layouts.app')

@section('title', 'Configuración del Negocio')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Configuración del Negocio</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('configuracion.negocio.actualizar') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre del Negocio *</label>
                        <input type="text" name="nombre_negocio" class="form-control" value="{{ $settings->nombre_negocio ?? '' }}" required>
                        <small class="text-muted">Este nombre aparecerá en los tickets</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Logo del Negocio</label>
                        @if($settings && $settings->logotipo)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $settings->logotipo) }}" alt="Logotipo" style="max-width: 200px; max-height: 100px;">
                            </div>
                        @endif
                        <input type="file" name="logotipo" class="form-control" accept="image/*">
                        <small class="text-muted">Formatos: JPG, PNG, GIF. Máximo 2MB. Se imprimirá en el encabezado de los tickets</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">RFC</label>
                            <input type="text" name="rfc" class="form-control" value="{{ $settings->rfc ?? '' }}">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" value="{{ $settings->telefono ?? '' }}">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <textarea name="direccion" class="form-control" rows="2">{{ $settings->direccion ?? '' }}</textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mensaje del Ticket</label>
                        <textarea name="mensaje_comprobante" class="form-control" rows="2">{{ $settings->mensaje_comprobante ?? '¡GRACIAS POR SU COMPRA!' }}</textarea>
                        <small class="text-muted">Mensaje que aparecerá al final del ticket</small>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Configuración de Impuestos</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 form-check form-switch">
                                <input type="hidden" name="cobrar_impuesto" value="0">
                                <input type="checkbox" name="cobrar_impuesto" class="form-check-input" id="chargeTax" value="1" {{ ($settings->cobrar_impuesto ?? true) ? 'checked' : '' }} onchange="toggleTaxPercentage()">
                                <label class="form-check-label" for="chargeTax">Cobrar IVA</label>
                            </div>
                            
                            <div class="mb-3" id="taxPercentageGroup">
                                <label class="form-label">Porcentaje de IVA (%)</label>
                                <input type="number" name="porcentaje_impuesto" class="form-control" value="{{ $settings->porcentaje_impuesto ?? 15 }}" step="0.01" min="0" max="100">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar Configuración
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Vista Previa del Ticket</h5>
            </div>
            <div class="card-body">
                <div style="font-family: 'Courier New', monospace; background: white; padding: 20px; border: 1px solid #333; max-width: 300px; margin: 0 auto;">
                    <div style="text-align: center;">
                        @if($settings && $settings->logotipo)
                            <div style="margin-bottom: 8px;">
                                <img src="{{ asset('storage/' . $settings->logotipo) }}" style="max-width: 150px; max-height: 75px;">
                            </div>
                        @endif
                        <strong style="font-size: 16px;">{{ $settings->nombre_negocio ?? 'MI NEGOCIO' }}</strong><br>
                        <small>RFC: {{ $settings->rfc ?? 'XAXX010101000' }}</small><br>
                        <small>Tel: {{ $settings->telefono ?? '(02) 000-0000' }}</small>
                    </div>
                    <hr style="border: none; border-top: 1px dashed #000; margin: 10px 0;">
                    <small>
                        Ticket: TKT-000001<br>
                        Fecha: {{ now()->format('d/m/Y H:i:s') }}
                    </small>
                    <hr style="border: none; border-top: 1px dashed #000; margin: 10px 0;">
                    <small>
                        <strong>2 x Producto Ejemplo</strong><br>
                        <span style="padding-left: 15px;">P.U.: $10.00</span><br>
                        <div style="text-align: right; font-weight: bold;">$20.00</div>
                    </small>
                    <hr style="border: none; border-top: 1px dashed #000; margin: 10px 0;">
                    <small>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Subtotal:</span>
                            <span>$20.00</span>
                        </div>
                        @if($settings->cobrar_impuesto ?? true)
                        <div style="display: flex; justify-content: space-between;">
                            <span>Impuesto ({{ $settings->porcentaje_impuesto ?? 15 }}%):</span>
                            <span>$3.20</span>
                        </div>
                        @endif
                        <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: bold;">
                            <span>TOTAL:</span>
                            <span>${{ ($settings->cobrar_impuesto ?? true) ? '23.00' : '20.00' }}</span>
                        </div>
                    </small>
                    <hr style="border: none; border-top: 1px dashed #000; margin: 10px 0;">
                    <div style="text-align: center; font-weight: bold;">
                        <small>{{ $settings->mensaje_comprobante ?? '¡GRACIAS POR SU COMPRA!' }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleTaxPercentage() {
    const checkbox = document.getElementById('chargeTax');
    const group = document.getElementById('taxPercentageGroup');
    group.style.display = checkbox.checked ? 'block' : 'none';
}
toggleTaxPercentage();
</script>
@endpush
@endsection
