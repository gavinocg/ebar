@extends('layouts.sidebar')

@section('title', 'Arqueos de caja')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Arqueos de caja</h4>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Cajero</label>
                <select name="usuario_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($usuarios as $usuario)
                        <option value="{{ $usuario->id }}" @selected((string) $usuarioSeleccionado === (string) $usuario->id)>
                            {{ $usuario->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Caja</label>
                <select name="caja_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach ($cajas as $caja)
                        <option value="{{ $caja->id }}" @selected((string) $cajaSeleccionada === (string) $caja->id)>
                            {{ $caja->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Sucursal</label>
                <select name="sucursal_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach ($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" @selected((string) $sucursalSeleccionada === (string) $sucursal->id)>
                            {{ $sucursal->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Turnos y diferencias</h5>
    </div>
    <div class="card-body">
        @if($turnos->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Cajero</th>
                            <th>Caja</th>
                            <th>Sucursal</th>
                            <th>Apertura</th>
                            <th>Cierre</th>
                            <th class="text-end">Esperado</th>
                            <th class="text-end">Contado</th>
                            <th class="text-end">Diferencia</th>
                            <th>Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($turnos as $turno)
                            <tr>
                                <td class="fw-semibold">{{ $turno->usuario?->nombre }}</td>
                                <td>{{ $turno->caja?->nombre }}</td>
                                <td>{{ $turno->caja?->sucursal?->nombre ?? '—' }}</td>
                                <td>{{ $turno->abierto_en->format('d/m/Y H:i') }}</td>
                                <td>{{ $turno->cerrado_en?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="text-end">${{ number_format((float) $turno->efectivo_esperado, 2) }}</td>
                                <td class="text-end">${{ number_format((float) $turno->efectivo_contado, 2) }}</td>
                                <td class="text-end {{ (float) $turno->diferencia != 0 ? 'text-danger fw-bold' : '' }}">
                                    ${{ number_format((float) $turno->diferencia, 2) }}
                                </td>
                                <td>
                                    @if ($turno->estado === 'abierta')
                                        <span class="badge bg-success">Abierto</span>
                                    @else
                                        <span class="badge bg-secondary">Cerrado</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('caja.turno-detalle', $turno) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Detalle
                                    </a>
                                    @if ($turno->estado === 'cerrada')
                                        <form method="POST" action="{{ route('caja.reabrir', $turno) }}" class="d-inline" onsubmit="return confirm('¿Reabrir este turno? Su cierre quedará anulado.')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-warning">
                                                <i class="bi bi-arrow-repeat"></i> Reabrir
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted text-center">No hay arqueos registrados.</p>
        @endif
    </div>
</div>
@endsection