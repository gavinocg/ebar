@extends('layouts.sidebar')

@section('title', 'Editar Rol: ' . $rol->nombre)

@section('content')
<div class="mb-4">
    <h4><i class="bi bi-shield-lock"></i> Editar Rol: {{ $rol->nombre }}</h4>
</div>

<form action="{{ route('roles.update', $rol) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card shadow-sm mb-4">
        <div class="card-header"><strong>Información General</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nombre" class="form-label">Nombre *</label>
                    <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $rol->nombre) }}" required maxlength="50">
                    @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <input type="text" name="descripcion" id="descripcion" class="form-control"
                           value="{{ old('descripcion', $rol->descripcion) }}" maxlength="255">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header"><strong>Permisos</strong></div>
        <div class="card-body">
            @error('permisos') <div class="alert alert-danger">{{ $message }}</div> @enderror

            <div class="mb-3">
                <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="toggleAll(true)">Seleccionar todos</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll(false)">Deseleccionar todos</button>
            </div>

            @foreach($todosPermisos as $modulo => $perms)
                <div class="mb-3">
                    <h6 class="text-primary mb-2">{{ $modulo }}</h6>
                    <div class="row">
                        @foreach($perms as $perm)
                        <div class="col-md-4 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input permiso-check" type="checkbox"
                                       name="permisos[]" value="{{ $perm->id }}"
                                       id="perm_{{ $perm->id }}"
                                       {{ in_array($perm->id, old('permisos', $permisosAsignados)) ? 'checked' : '' }}>
                                <label class="form-check-label" for="perm_{{ $perm->id }}">
                                    {{ $perm->nombre }}
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @if(!$loop->last)<hr>@endif
            @endforeach
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar Cambios</button>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

<script>
function toggleAll(checked) {
    document.querySelectorAll('.permiso-check').forEach(cb => cb.checked = checked);
}
</script>
@endsection
