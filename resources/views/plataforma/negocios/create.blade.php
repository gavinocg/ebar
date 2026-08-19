@extends('layouts.sidebar')

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

<form method="POST" action="{{ route('plataforma.negocios.store') }}" enctype="multipart/form-data" class="row g-3 bg-white shadow-sm rounded p-4">
    @csrf

    <div class="col-12">
        <h5 class="text-muted">Bar</h5>
    </div>

    <div class="col-md-6">
        <label class="form-label">Nombre del bar *</label>
        <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">RUC *</label>
        <input type="text" name="ruc" class="form-control" value="{{ old('ruc') }}" maxlength="13">
        <div class="form-text">13 dígitos. Se valida contra el SRI (persona natural o jurídica).</div>
    </div>

    <div class="col-md-6">
        <label class="form-label">Nº sucursales contratadas (xNS) *</label>
        <input type="number" name="numero_sucursales_contratadas" min="1" max="100" class="form-control" value="{{ old('numero_sucursales_contratadas', 1) }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Logo</label>
        <input type="file" name="logo" class="form-control" accept="image/*">
    </div>

    <div class="col-md-6">
        <label class="form-label">Zona horaria</label>
        <select name="zona_horaria" class="form-select">
            @foreach ($zonasHorarias as $clave => $etiqueta)
                <option value="{{ $clave }}" @selected(old('zona_horaria', 'America/Guayaquil') === $clave)>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Moneda</label>
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

    <div class="col-md-6">
        <label class="form-label">Cajeros contratados en sucursal inicial (xNC)</label>
        <input type="number" name="n_cajeros_sucursal" min="0" max="50" class="form-control" value="{{ old('n_cajeros_sucursal', 1) }}">
        <div class="form-text">0 = ilimitado.</div>
    </div>

    <div class="col-12">
        <hr>
        <h5 class="text-muted">Propietario inicial</h5>
        <p class="text-muted small">
            Se generará una contraseña temporal automáticamente y el propietario deberá cambiarla en su primer ingreso.
        </p>
    </div>

    <div class="col-md-6">
        <label class="form-label">Nombre del propietario *</label>
        <input type="text" name="nombre_admin" class="form-control" value="{{ old('nombre_admin') }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Correo del propietario *</label>
        <input type="email" name="correo_admin" class="form-control" value="{{ old('correo_admin') }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Cédula del propietario</label>
        <input type="text" name="cedula_admin" class="form-control" value="{{ old('cedula_admin') }}" maxlength="10">
        <div class="form-text">10 dígitos, se valida su dígito verificador.</div>
    </div>

    <div class="col-md-6">
        <label class="form-label">Celular del propietario</label>
        <input type="text" name="celular_admin" class="form-control" value="{{ old('celular_admin') }}" maxlength="20">
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('plataforma.negocios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Crear bar
        </button>
    </div>
</form>
@endsection