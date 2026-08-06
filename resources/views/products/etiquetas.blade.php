@extends('layouts.sidebar')

@section('title', 'Impresión de etiquetas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Impresión de etiquetas</h4>
</div>

<form method="POST" action="{{ route('etiquetas.imprimir') }}" target="_blank">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Copias por producto</label>
                    <input type="number" name="copias" class="form-control" min="1" max="100" value="1">
                </div>
            </div>
            <label class="form-label mb-2">Selecciona los productos a etiquetar:</label>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width:40px"><input type="checkbox" id="seleccionarTodo" checked></th>
                            <th>Producto</th>
                            <th class="text-end">Precio</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productos as $producto)
                            <tr>
                                <td><input type="checkbox" name="productos[]" value="{{ $producto->id }}" class="fila-producto" checked></td>
                                <td class="fw-semibold">{{ $producto->nombre }}</td>
                                <td class="text-end">${{ number_format($producto->precio, 2) }}</td>
                                <td>{{ $producto->esta_activo ? 'Activo' : 'Inactivo' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between">
                <p class="text-muted small">Se generará una página imprimible con etiquetas de precios.</p>
                <button class="btn btn-primary"><i class="bi bi-printer"></i> Generar etiquetas</button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.getElementById('seleccionarTodo').addEventListener('change', function () {
    document.querySelectorAll('.fila-producto').forEach(cb => cb.checked = this.checked);
});
</script>
@endpush
@endsection