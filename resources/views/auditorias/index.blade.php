@extends('layouts.sidebar')

@section('title', 'Auditoría')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Auditoría de operaciones</h4>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Módulo</label>
                <select name="modulo" class="form-select">
                    <option value="">Todos</option>
                    @foreach($modulos as $modulo)
                        <option value="{{ $modulo }}" @selected(request('modulo') === $modulo)>{{ $modulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Acción</label>
                <select name="accion" class="form-select">
                    <option value="">Todas</option>
                    @foreach($acciones as $accion)
                        <option value="{{ $accion }}" @selected(request('accion') === $accion)>{{ $accion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($registros->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Módulo</th>
                            <th>Acción</th>
                            <th>Descripción</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registros as $registro)
                            <tr>
                                <td>{{ $registro->created_at->format('d/m/Y H:i') }}</td>
                                <td class="fw-semibold">{{ $registro->usuario?->nombre ?? '—' }}</td>
                                <td><span class="badge bg-secondary">{{ $registro->modulo }}</span></td>
                                <td>{{ $registro->accion }}</td>
                                <td>{{ $registro->descripcion }}</td>
                                <td><code>{{ $registro->direccion_ip }}</code></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $registros->links() }}
        @else
            <p class="text-muted text-center">No hay registros de auditoría.</p>
        @endif
    </div>
</div>
@endsection