@extends('layouts.sidebar')

@section('title', 'Categorías')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Nueva Categoría</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('categorias.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" class="form-control form-control-color w-100" value="#334155" title="Color de categoría">
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="form-label">Icono Bootstrap</label>
                            <select name="icono" class="form-select selector-icono-comida" data-preview="vista-icono-nueva">
                                <option value="bi bi-cup-straw">Bebida</option>
                                <option value="bi bi-cup-hot">Café o bebida caliente</option>
                                <option value="bi bi-egg-fried">Alimentos</option>
                                <option value="bi bi-cake2">Postres</option>
                                <option value="bi bi-ice-cream">Helados</option>
                                <option value="bi bi-apple">Frutas</option>
                                <option value="bi bi-basket2">Canasta o compras</option>
                                <option value="bi bi-people">Servicio</option>
                            </select>
                            <div class="mt-2 text-muted small">Vista previa: <i id="vista-icono-nueva" class="bi bi-cup-straw fs-5"></i></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Imagen</label>
                        <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <small class="text-muted">JPG, PNG o WebP. Máximo 2 MB.</small>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <label class="form-label">Orden</label>
                            <input type="number" name="orden" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-sm-6 mb-3 form-check form-switch pt-4">
                            <input type="hidden" name="esta_activa" value="0">
                            <input type="checkbox" name="esta_activa" class="form-check-input" value="1" checked>
                            <label class="form-check-label">Categoría activa</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Crear
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Categorías</h5>
            </div>
            <div class="card-body">
                @if($categories->count() > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Productos</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $category)
                                    <tr>
                                        <td>
                                            <span class="d-inline-flex align-items-center gap-2">
                                                @if($category->imagen_path)
                                                    <img src="{{ asset('storage/' . $category->imagen_path) }}" alt="" width="36" height="36" class="rounded" style="object-fit:cover">
                                                @else
                                                    <span class="rounded d-inline-flex align-items-center justify-content-center text-white" style="width:36px;height:36px;background:{{ $category->color }}"><i class="{{ $category->icono ?: 'bi bi-tag' }}"></i></span>
                                                @endif
                                                {{ $category->nombre }}
                                            </span>
                                        </td>
                                        <td>{{ $category->descripcion ?? '-' }}</td>
                                        <td><span class="badge bg-info">{{ $category->productos_count }}</span></td>
                                        <td>
                                            @if($category->esta_activa)
                                                <span class="badge bg-success">Activa</span>
                                            @else
                                                <span class="badge bg-secondary">Inactiva</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $category->id }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="{{ route('categorias.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar categoría?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="editModal{{ $category->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('categorias.update', $category) }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Editar Categoría</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Nombre</label>
                                                            <input type="text" name="nombre" class="form-control" value="{{ $category->nombre }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Descripción</label>
                                                            <textarea name="descripcion" class="form-control" rows="3">{{ $category->descripcion }}</textarea>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-sm-6 mb-3">
                                                                <label class="form-label">Color</label>
                                                                <input type="color" name="color" class="form-control form-control-color w-100" value="{{ $category->color }}">
                                                            </div>
                                                            <div class="col-sm-6 mb-3">
                                                                <label class="form-label">Icono Bootstrap</label>
                                                                <select name="icono" class="form-select selector-icono-comida" data-preview="vista-icono-{{ $category->id }}">
                                                                    @foreach([
                                                                        'bi bi-cup-straw' => 'Bebida',
                                                                        'bi bi-cup-hot' => 'Café o bebida caliente',
                                                                        'bi bi-egg-fried' => 'Alimentos',
                                                                        'bi bi-cake2' => 'Postres',
                                                                        'bi bi-ice-cream' => 'Helados',
                                                                        'bi bi-apple' => 'Frutas',
                                                                        'bi bi-basket2' => 'Canasta o compras',
                                                                        'bi bi-people' => 'Servicio',
                                                                    ] as $icono => $etiqueta)
                                                                        <option value="{{ $icono }}" {{ ($category->icono ?: 'bi bi-cup-straw') === $icono ? 'selected' : '' }}>{{ $etiqueta }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <div class="mt-2 text-muted small">Vista previa: <i id="vista-icono-{{ $category->id }}" class="{{ $category->icono ?: 'bi bi-cup-straw' }} fs-5"></i></div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Reemplazar imagen</label>
                                                            <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/png,image/webp">
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-sm-6 mb-3">
                                                                <label class="form-label">Orden</label>
                                                                <input type="number" name="orden" class="form-control" min="0" value="{{ $category->orden }}">
                                                            </div>
                                                            <div class="col-sm-6 mb-3 form-check form-switch pt-4">
                                                                <input type="hidden" name="esta_activa" value="0">
                                                                <input type="checkbox" name="esta_activa" class="form-check-input" value="1" {{ $category->esta_activa ? 'checked' : '' }}>
                                                                <label class="form-check-label">Categoría activa</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary">Guardar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">No hay categorías aún</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.selector-icono-comida').forEach(select => {
    const preview = document.getElementById(select.dataset.preview);
    const actualizar = () => {
        preview.className = `${select.value} fs-5`;
    };

    select.addEventListener('change', actualizar);
    actualizar();
});
</script>
@endpush
