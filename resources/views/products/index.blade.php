@extends('layouts.sidebar')

@section('title', 'Productos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Productos</h4>
    <a href="{{ route('productos.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nuevo Producto
    </a>
    <a href="{{ route('reportes.inventario') }}" class="btn btn-outline-secondary" title="Reporte de inventario">
        <i class="bi bi-box"></i>
    </a>
    <a href="{{ route('etiquetas.index') }}" class="btn btn-outline-secondary" title="Imprimir etiquetas">
        <i class="bi bi-tag"></i>
    </a>
    <a href="{{ route('productos.exportar') }}" class="btn btn-outline-secondary" title="Exportar CSV">
        <i class="bi bi-download"></i>
    </a>
    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importarModal" title="Importar CSV">
        <i class="bi bi-upload"></i>
    </button>
    <a href="{{ route('inventario.historial') }}" class="btn btn-outline-primary">
        <i class="bi bi-clock-history"></i> Historial
    </a>
</div>

<div class="card">
    <div class="card-body">
        @if($products->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Sucursal</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Código</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr>
                                <td>{{ $product->nombre }}</td>
                                <td>{{ $product->categoria->nombre }}</td>
                                <td>{{ $product->sucursal->nombre ?? 'Todas' }}</td>
                                <td>${{ number_format($product->precio, 2) }}</td>
                                <td>
                                    @if(!$product->maneja_existencias)
                                        <span class="badge bg-info">Ilimitado</span>
                                    @elseif($product->existencias == 0)
                                        <span class="badge bg-danger">Agotado</span>
                                    @elseif($product->nivel_minimo && $product->existencias <= $product->nivel_minimo)
                                        <span class="badge bg-warning">Bajo ({{ $product->existencias }})</span>
                                    @elseif($product->nivel_minimo && $product->existencias <= $product->nivel_minimo + 5)
                                        <span class="badge bg-warning">{{ $product->existencias }}</span>
                                    @else
                                        <span class="badge bg-success">{{ $product->existencias }}</span>
                                    @endif
                                </td>
                                <td>{{ $product->codigo_barras ?? '-' }}</td>
                                <td>
                                    @if($product->esta_activo)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('productos.edit', $product) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('productos.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar producto?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center">
                {{ $products->links() }}
            </div>
        @else
            <p class="text-muted text-center">No hay productos aún</p>
        @endif
    </div>
</div>

<div class="modal fade" id="importarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('productos.importar') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Importar productos (CSV)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Archivo CSV</label>
                        <input type="file" name="archivo" class="form-control" accept=".csv,.txt" required>
                    </div>
                    <p class="text-muted small mb-0">
                        Columnas: <code>nombre, categoria, precio, existencias, nivel_minimo, codigo_barras, sucursal, maneja_existencias</code>.
                        Si el código de barras existe, se actualiza el producto.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary"><i class="bi bi-upload"></i> Importar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
