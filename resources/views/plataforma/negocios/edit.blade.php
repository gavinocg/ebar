@extends('layouts.app')

@section('title', 'Editar bar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="badge bg-dark">super_admin</span>
        <h1 class="h3 mt-2">Editar {{ $negocio->nombre }}</h1>
    </div>
    <a href="{{ route('plataforma.negocios.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<form method="POST" action="{{ route('plataforma.negocios.update', $negocio) }}" class="row g-3 bg-white shadow-sm rounded p-4">
    @csrf
    @method('PUT')

    <div class="col-md-6">
        <label class="form-label">Nombre del bar *</label>
        <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $negocio->nombre) }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Identificador único *</label>
        <input type="text" name="identificador" class="form-control" value="{{ old('identificador', $negocio->identificador) }}" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Zona horaria *</label>
        <select name="zona_horaria" class="form-select">
            @foreach ($zonasHorarias as $clave => $etiqueta)
                <option value="{{ $clave }}" @selected(old('zona_horaria', $negocio->zona_horaria) === $clave)>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Moneda *</label>
        <select name="moneda" class="form-select">
            @foreach ($monedas as $clave => $etiqueta)
                <option value="{{ $clave }}" @selected(old('moneda', $negocio->moneda) === $clave)>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Plan de membresía *</label>
        <select name="plan_id" class="form-select">
            @foreach ($planes as $plan)
                <option value="{{ $plan->id }}" @selected(old('plan_id', $negocio->membresia?->plan_id) == $plan->id)>
                    {{ $plan->nombre }} — ${{ number_format($plan->precio_mensual, 2) }}/mes
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <div class="form-check form-switch mt-4">
            <input class="form-check-input" type="checkbox" name="esta_activo" id="esta_activo" value="1" @checked(old('esta_activo', $negocio->esta_activo))>
            <label class="form-check-label" for="esta_activo">Bar activo</label>
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('plataforma.negocios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Guardar
        </button>
    </div>
</form>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">Membresía del bar</h5>
    </div>
    <div class="card-body">
        @if ($negocio->membresia)
            <div class="row align-items-center g-3">
                <div class="col-md-3">
                    <div class="text-muted small">Plan</div>
                    <div class="fw-semibold">{{ $negocio->membresia->plan?->nombre }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Estado</div>
                    @switch($negocio->membresia->estado)
                        @case('activa')
                            <span class="badge bg-success">Activa</span>
                            @break
                        @case('prueba')
                            <span class="badge bg-info text-dark">Prueba</span>
                            @break
                        @case('suspendida')
                            <span class="badge bg-warning text-dark">Suspendida</span>
                            @break
                        @case('vencida')
                            <span class="badge bg-danger">Vencida</span>
                            @break
                        @case('cancelada')
                            <span class="badge bg-secondary">Cancelada</span>
                            @break
                        @default
                            <span class="badge bg-secondary">{{ $negocio->membresia->estado }}</span>
                    @endswitch
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Vence</div>
                    <div class="fw-semibold">{{ $negocio->membresia->fecha_vencimiento->format('d/m/Y') }}</div>
                </div>
                <div class="col-md-3 d-flex gap-2 justify-content-md-end flex-wrap">
                    <form method="POST" action="{{ route('plataforma.negocios.membresia.renovar', $negocio) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-success">
                            <i class="bi bi-arrow-repeat"></i> Renovar
                        </button>
                    </form>
                    @if ($negocio->membresia->estado === 'suspendida')
                        <form method="POST" action="{{ route('plataforma.negocios.membresia.reactivar', $negocio) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-play"></i> Reactivar
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('plataforma.negocios.membresia.suspender', $negocio) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-pause"></i> Suspender
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @else
            <p class="text-muted mb-0">Este bar no tiene membresía asignada.</p>
        @endif
    </div>
</div>
@endsection