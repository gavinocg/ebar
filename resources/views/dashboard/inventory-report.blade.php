@extends('layouts.app')

@section('title', 'Reporte de Inventario')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Reporte de Inventario</h4>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6>Productos</h6>
                <h2>{{ $totalProducts }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6>Stock Total</h6>
                <h2>{{ $totalStock }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h6>Valor Inventario</h6>
                <h2>${{ number_format($totalValue, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h6>Stock Bajo</h6>
                <h2>{{ $lowStock->count() }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Productos por Categoría</h5>
            </div>
            <div class="card-body">
                @if($byCategory->count() > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Categoría</th>
                                    <th>Productos</th>
                                    <th>Stock Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byCategory as $cat)
                                    <tr>
                                        <td>{{ $cat->category_name }}</td>
                                        <td><span class="badge bg-info">{{ $cat->count }}</span></td>
                                        <td>{{ $cat->total_stock }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">No hay datos</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0">Productos con Stock Bajo (≤10)</h5>
            </div>
            <div class="card-body">
                @if($lowStock->count() > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStock as $product)
                                    <tr>
                                        <td>{{ $product->name }}</td>
                                        <td>
                                            <span class="badge bg-{{ $product->stock == 0 ? 'danger' : 'warning' }}">
                                                {{ $product->stock }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">Todo el inventario está bien surtido</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h5 class="mb-0">Inventario Completo</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category->name }}</td>
                            <td>${{ number_format($product->price, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $product->stock == 0 ? 'danger' : ($product->stock <= 10 ? 'warning' : 'success') }}">
                                    {{ $product->stock }}
                                </span>
                            </td>
                            <td>${{ number_format($product->stock * $product->price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
