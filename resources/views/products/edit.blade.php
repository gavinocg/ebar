@extends('layouts.sidebar')

@section('title', 'Editar Producto')

@section('content')
<div class="mb-3">
    <a href="{{ route('productos.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Editar Producto: {{ $product->nombre }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('productos.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Categoría</label>
                    <select name="categoria_id" class="form-select" required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $product->categoria_id == $category->id ? 'selected' : '' }}>
                                {{ $category->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Sucursal</label>
                    <select name="sucursal_id" class="form-select">
                        <option value="">Todas las sucursales</option>
                        @foreach($sucursales as $sucursal)
                            <option value="{{ $sucursal->id }}" @selected($product->sucursal_id === $sucursal->id)>{{ $sucursal->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Código de Barras</label>
                    <input type="text" name="codigo_barras" class="form-control" value="{{ $product->codigo_barras }}">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Nombre del Producto</label>
                <input type="text" name="nombre" class="form-control" value="{{ $product->nombre }}" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3">{{ $product->descripcion }}</textarea>
            </div>

            <div class="card bg-light border-0 mb-3">
                <div class="card-body">
                    <h6 class="card-title">Apariencia en el TPV</h6>
                    @if($product->imagen_path)
                        <img src="{{ asset('storage/' . $product->imagen_path) }}" alt="{{ $product->nombre }}" class="rounded mb-3" width="120" height="120" style="object-fit:cover">
                    @endif
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Reemplazar imagen</label>
                            <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Color de tarjeta</label>
                            <input type="color" name="color" class="form-control form-control-color w-100" value="{{ $product->color ?: '#ffffff' }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Distintivo</label>
                            <input type="text" name="distintivo" class="form-control" maxlength="40" value="{{ $product->distintivo }}">
                        </div>
                    </div>
                    <div class="row align-items-end">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Color del distintivo</label>
                            <input type="color" name="distintivo_color" class="form-control form-control-color w-100" value="{{ $product->distintivo_color ?: '#16a34a' }}">
                        </div>
                        <div class="col-md-4 mb-3 form-check form-switch">
                            <input type="hidden" name="destacado" value="0">
                            <input type="checkbox" name="destacado" class="form-check-input" value="1" {{ $product->destacado ? 'checked' : '' }}>
                            <label class="form-check-label">Producto destacado</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Precio</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="precio" class="form-control" step="0.01" min="0" value="{{ $product->precio }}" required>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Descuento (%)</label>
                    <div class="input-group">
                        <input type="number" name="descuento" class="form-control" step="0.01" min="0" max="100" value="{{ $product->descuento ?? 0 }}">
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text">Descuento automático aplicado al vender este producto.</div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Stock</label>
                    <input type="number" name="existencias" id="existenciasProducto" class="form-control" min="0" value="{{ $product->existencias }}" {{ $product->maneja_existencias ? '' : 'disabled' }}>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Nivel mínimo</label>
                    <input type="number" name="nivel_minimo" id="nivelMinimoProducto" class="form-control" min="0" value="{{ $product->nivel_minimo ?? 0 }}" {{ $product->maneja_existencias ? '' : 'disabled' }}>
                    <div class="form-text">Alerta cuando las existencias bajen de este valor.</div>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Estado</label>
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="esta_activo" value="0">
                        <input type="checkbox" name="esta_activo" class="form-check-input" value="1" {{ $product->esta_activo ? 'checked' : '' }}>
                        <label class="form-check-label">Producto Activo</label>
                    </div>
                </div>
            </div>

            <div class="form-check form-switch mb-3">
                <input type="hidden" name="maneja_existencias" value="0">
                <input type="checkbox" name="maneja_existencias" class="form-check-input" id="manejaExistencias" value="1" {{ $product->maneja_existencias ? 'checked' : '' }}>
                <label class="form-check-label" for="manejaExistencias">Controlar existencias de este producto</label>
                <div class="form-text">Desactívalo para venderlo sin límite de inventario.</div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="{{ route('productos.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Actualizar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const switchExistencias = document.getElementById('manejaExistencias');
const campoExistencias = document.getElementById('existenciasProducto');
const campoNivelMinimo = document.getElementById('nivelMinimoProducto');
const actualizarCampoExistencias = () => {
    const activo = switchExistencias.checked;
    campoExistencias.disabled = !activo;
    campoExistencias.required = activo;
    campoNivelMinimo.disabled = !activo;
    if (!activo) {
        campoExistencias.value = 0;
        campoNivelMinimo.value = 0;
    }
};
switchExistencias.addEventListener('change', actualizarCampoExistencias);
actualizarCampoExistencias();
</script>
@endpush
