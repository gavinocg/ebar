@extends('layouts.app')

@section('title', 'Bares')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="badge bg-dark">super_admin</span>
        <h1 class="h3 mt-2">Bares registrados</h1>
    </div>
    <a href="{{ route('plataforma.negocios.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nuevo bar
    </a>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm rounded">
        <thead class="table-light">
            <tr>
                <th>Bar</th>
                <th>Identificador</th>
                <th>Sucursales</th>
                <th>Plan</th>
                <th>Membresía</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($negocios as $negocio)
                <tr>
                    <td class="fw-semibold">{{ $negocio->nombre }}</td>
                    <td><code>{{ $negocio->identificador }}</code></td>
                    <td>{{ $negocio->sucursales->count() }}</td>
                    <td>
                        @if ($negocio->membresia?->plan)
                            <span class="badge bg-info text-dark">{{ $negocio->membresia->plan->nombre }}</span>
                        @else
                            <span class="badge bg-secondary">Sin plan</span>
                        @endif
                    </td>
                    <td>
                        @if ($negocio->membresia)
                            {{ $negocio->membresia->estado }}
                            <br>
                            <small class="text-muted">vence {{ $negocio->membresia->fecha_vencimiento->format('d/m/Y') }}</small>
                        @else
                            <span class="badge bg-secondary">Sin membresía</span>
                        @endif
                    </td>
                    <td>
                        @if ($negocio->esta_activo)
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-danger">Suspendido</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('plataforma.negocios.edit', $negocio) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <form action="{{ route('plataforma.negocios.destroy', $negocio) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este bar? Esta acción no se puede deshacer.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No hay bares registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection