@extends('layouts.app')

@section('title', 'Administración de e-Bar')

@section('content')
<div class="mb-4">
    <span class="badge bg-dark">super_admin</span>
    <h1 class="h3 mt-2">Administración de e-Bar</h1>
    <p class="text-muted mb-0">Gestiona bares escolares, membresías y administradores desde la plataforma.</p>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Bares registrados</div>
                <div class="display-6 fw-semibold">{{ $totalNegocios }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Bares activos</div>
                <div class="display-6 fw-semibold text-success">{{ $negociosActivos }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Membresías activas</div>
                <div class="display-6 fw-semibold text-primary">{{ $membresiasActivas }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
