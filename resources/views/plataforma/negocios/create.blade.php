@extends('layouts.app')

@section('title', 'Nuevo bar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="badge bg-dark">super_admin</span>
        <h1 class="h3 mt-2">Nuevo bar</h1>
    </div>
    <a href="{{ route('plataforma.negocios.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<form method="POST" action="{{ route('plataforma.negocios.store') }}" class="row g-3 bg-white shadow-sm rounded p-4">
    @csrf

    <div class="col-md-6">
        <label class="form-label">Nombre del bar *</label>
        <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Identificador único *</label>
        <input type="text" name="identificador" class="form-control" value="{{ old('identificador') }}" required>
        <div class="form-text">Solo letras, números, guiones y guiones bajos. Ej: bar-san-felipe</div>
    </div>

    <div class="col-md-6">
        <label class="form-label">Zona horaria *</label>
        <select name="zona_horaria" class="form-select">
            @foreach ($zonasHorarias as $clave => $etiqueta)
                <option value="{{ $clave }}" @selected(old('zona_horaria', 'America/Guayaquil') === $clave)>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Moneda *</label>
        <select name="moneda" class="form-select">
            @foreach ($monedas as $clave => $etiqueta)
                <option value="{{ $clave }}" @selected(old('moneda', 'USD') === $clave)>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <label class="form-label">Plan de membresía *</label>
        <select name="plan_id" class="form-select">
            @foreach ($planes as $plan)
                <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>
                    {{ $plan->nombre }} — ${{ number_format($plan->precio_mensual, 2) }}/mes
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Sucursal inicial</label>
        <input type="text" name="nombre_sucursal" class="form-control" value="{{ old('nombre_sucursal', 'Sucursal principal') }}">
    </div>

    <div class="col-12">
        <hr>
        <h5 class="text-muted">Administrador inicial</h5>
    </div>

    <div class="col-md-6">
        <label class="form-label">Nombre del administrador *</label>
        <input type="text" name="nombre_admin" class="form-control" value="{{ old('nombre_admin') }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Correo del administrador *</label>
        <input type="email" name="correo_admin" class="form-control" value="{{ old('correo_admin') }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Contraseña *</label>
        <input type="password" name="clave_admin" class="form-control" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Confirmar contraseña *</label>
        <input type="password" name="clave_admin_confirmation" class="form-control" required>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('plataforma.negocios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Crear bar
        </button>
    </div>
</form>
@endsection