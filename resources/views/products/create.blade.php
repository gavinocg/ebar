@extends('layouts.app')

@section('title', 'Nuevo Producto')

@section('content')
<div class="mb-3">
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Nuevo Producto</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('products.store') }}" method="POST">
            @csrf
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Categoría</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Código de Barras</label>
                    <input type="text" name="barcode" class="form-control" placeholder="Opcional">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Nombre del Producto</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Precio</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Stock Inicial</label>
                    <input type="number" name="stock" class="form-control" min="0" value="0" required>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
