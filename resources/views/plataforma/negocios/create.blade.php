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

<form method="POST" action="{{ route('plataforma.negocios.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <i class="bi bi-shop me-1"></i> <strong>Datos del bar</strong>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nombre" class="form-label">Nombre del bar *</label>
                    <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre') }}" required>
                    @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="ruc" class="form-label">RUC *</label>
                    <input type="text" name="ruc" id="ruc" class="form-control @error('ruc') is-invalid @enderror"
                           value="{{ old('ruc') }}" maxlength="13">
                    @error('ruc') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">13 dígitos. Se valida contra el SRI (persona natural o jurídica).</div>
                </div>

                <div class="col-md-6">
                    <label for="logo" class="form-label">Logo</label>
                    <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
                </div>

                <div class="col-md-3">
                    <label for="zona_horaria" class="form-label">Zona horaria</label>
                    <select name="zona_horaria" id="zona_horaria" class="form-select">
                        @foreach ($zonasHorarias as $clave => $etiqueta)
                            <option value="{{ $clave }}" @selected(old('zona_horaria', 'America/Guayaquil') === $clave)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="moneda" class="form-label">Moneda</label>
                    <select name="moneda" id="moneda" class="form-select">
                        @foreach ($monedas as $clave => $etiqueta)
                            <option value="{{ $clave }}" @selected(old('moneda', 'USD') === $clave)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="nombre_sucursal" class="form-label">Sucursal inicial</label>
                    <input type="text" name="nombre_sucursal" id="nombre_sucursal" class="form-control"
                           value="{{ old('nombre_sucursal', 'Sucursal principal') }}">
                    <div class="form-text">Los límites de sucursales y cajeros se definen en el contrato.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <i class="bi bi-person-badge me-1"></i> <strong>Propietario inicial</strong>
        </div>
        <div class="card-body">
            <div class="alert alert-light border mb-3 small">
                Se generará una contraseña temporal automáticamente y el propietario deberá cambiarla en su primer ingreso.
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nombre_admin" class="form-label">Nombre del propietario *</label>
                    <input type="text" name="nombre_admin" id="nombre_admin" class="form-control @error('nombre_admin') is-invalid @enderror"
                           value="{{ old('nombre_admin') }}" required>
                    @error('nombre_admin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="correo_admin" class="form-label">Correo del propietario *</label>
                    <input type="email" name="correo_admin" id="correo_admin" class="form-control @error('correo_admin') is-invalid @enderror"
                           value="{{ old('correo_admin') }}" required>
                    @error('correo_admin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label for="cedula_admin" class="form-label">Cédula del propietario</label>
                    <input type="text" name="cedula_admin" id="cedula_admin" class="form-control @error('cedula_admin') is-invalid @enderror"
                           value="{{ old('cedula_admin') }}" maxlength="10">
                    @error('cedula_admin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">10 dígitos, se valida su dígito verificador.</div>
                </div>

                <div class="col-md-4">
                    <label for="celular_admin" class="form-label">Celular del propietario</label>
                    <input type="text" name="celular_admin" id="celular_admin" class="form-control @error('celular_admin') is-invalid @enderror"
                           value="{{ old('celular_admin') }}" maxlength="20">
                    @error('celular_admin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('plataforma.negocios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Crear bar</button>
    </div>
</form>
@endsection