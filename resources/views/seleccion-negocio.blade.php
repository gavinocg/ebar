@extends('layouts.app')

@section('title', 'Seleccionar bar')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body p-4">
                <h1 class="h4 mb-1">Selecciona tu bar</h1>
                <p class="text-muted">Elige a qué negocio deseas ingresar y, si aplica, su sucursal.</p>

                <div class="list-group mb-3">
                    @foreach ($negocios as $negocio)
                        <form method="POST" action="{{ route('negocio.seleccionar.guardar') }}">
                            @csrf
                            <input type="hidden" name="negocio_id" value="{{ $negocio->id }}">
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="bi bi-shop me-2"></i>
                                        <span class="fw-semibold">{{ $negocio->nombre }}</span>
                                    </span>
                                    @if ($negocio->sucursales->where('esta_activa', true)->count() > 1)
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            Ingresar <i class="bi bi-chevron-right"></i>
                                        </button>
                                    @endif
                                </div>
                                @php
                                    $sucursalesActivas = $negocio->sucursales->where('esta_activa', true);
                                @endphp
                                @if ($sucursalesActivas->count() > 1)
                                    <div class="mt-2">
                                        <label class="form-label small text-muted mb-1">Sucursal</label>
                                        <select name="sucursal_id" class="form-select form-select-sm">
                                            @foreach ($sucursalesActivas as $sucursal)
                                                <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @elseif ($sucursalesActivas->count() === 1)
                                    <button type="submit" class="btn btn-primary btn-sm mt-2">
                                        Ingresar <i class="bi bi-chevron-right"></i>
                                    </button>
                                @endif
                            </div>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection