@extends('layouts.app')

@section('title', 'Cajeros')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3">Cajeros</h1>
        <p class="text-muted mb-0">
            {{ $cajeros->where('esta_activa', true)->count() }} de {{ $limiteCajeros }} cajeros activos según tu plan.
        </p>
    </div>
    @unless ($limiteAlcanzado)
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearCajero" @disabled($limiteAlcanzado)>
            <i class="bi bi-person-plus"></i> Nuevo cajero
        </button>
    @else
        <span class="badge bg-warning text-dark">Límite de cajeros alcanzado</span>
    @endif
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm rounded">
        <thead class="table-light">
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>PIN</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cajeros as $cajero)
                <tr>
                    <td class="fw-semibold">{{ $cajero->usuario->nombre }}</td>
                    <td>{{ $cajero->usuario->correo }}</td>
                    <td>
                        @if ($cajero->usuario->pin)
                            <span class="text-muted">definido</span>
                        @else
                            <span class="badge bg-secondary">sin PIN</span>
                        @endif
                    </td>
                    <td>
                        @if ($cajero->esta_activa)
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-secondary">Desactivado</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editar{{ $cajero->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('cajeros.destroy', $cajero->usuario) }}" class="d-inline" onsubmit="return confirm('¿Desactivar este cajero? Conservará su historial.')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-person-x"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">No hay cajeros registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="modal fade" id="crearCajero" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('cajeros.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo cajero</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo *</label>
                        <input type="email" name="correo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" name="clave" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">PIN de 4 dígitos *</label>
                        <input type="password" name="pin" class="form-control" inputmode="numeric" maxlength="4" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach ($cajeros as $cajero)
    <div class="modal fade" id="editar{{ $cajero->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('cajeros.update', $cajero->usuario) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Editar cajero</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" value="{{ $cajero->usuario->nombre }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo *</label>
                            <input type="email" name="correo" class="form-control" value="{{ $cajero->usuario->correo }}" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Nuevo PIN (opcional)</label>
                            <input type="password" name="pin" class="form-control" inputmode="numeric" maxlength="4">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection