@extends('layouts.app')

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
        <form action="{{ route('productos.update', $product) }}" method="POST">
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
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Precio</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="precio" class="form-control" step="0.01" min="0" value="{{ $product->precio }}" required>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Stock</label>
                    <input type="number" name="existencias" class="form-control" min="0" value="{{ $product->existencias }}" required>
                </div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" name="esta_activo" class="form-check-input" value="1" {{ $product->esta_activo ? 'checked' : '' }}>
                <label class="form-check-label">Producto Activo</label>
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
