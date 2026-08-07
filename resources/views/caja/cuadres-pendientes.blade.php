@extends('layouts.sidebar')

@section('title', 'Cuadres pendientes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3">Cuadres pendientes de aprobación</h1>
        <p class="text-muted mb-0">Revisa y confirma los cierres de caja de tus cajeros.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-body">
        @if($pendientes->isEmpty())
            <p class="text-muted text-center py-4">No hay cuadres pendientes de aprobación.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Cajero</th>
                            <th>Fecha cierre</th>
                            <th>Esperado</th>
                            <th>Contado</th>
                            <th>Diferencia</th>
                            <th>Tipo</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendientes as $turno)
                            <tr>
                                <td class="fw-semibold">{{ $turno->usuario?->nombre }}</td>
                                <td>{{ $turno->cerrado_en?->format('d/m/Y H:i') }}</td>
                                <td>${{ number_format($turno->efectivo_esperado, 2) }}</td>
                                <td>${{ number_format($turno->efectivo_contado, 2) }}</td>
                                <td>
                                    @if($turno->diferencia == 0)
                                        <span class="badge bg-success">$0.00</span>
                                    @else
                                        <span class="badge bg-danger">${{ number_format($turno->diferencia, 2) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($turno->estado === 'pendiente_modificacion')
                                        <span class="badge bg-warning text-dark">Modificación</span>
                                    @else
                                        <span class="badge bg-info">Cuadre</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($turno->estado === 'pendiente_modificacion')
                                        <form method="POST" action="{{ route('cuadres.autorizar-modificacion', $turno) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success"><i class="bi bi-check"></i> Autorizar</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('cuadres.aprobar', $turno) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success"><i class="bi bi-check"></i> Aprobar</button>
                                        </form>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rechazar{{ $turno->id }}">
                                            <i class="bi bi-x"></i> Rechazar
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@foreach($pendientes as $turno)
    <div class="modal fade" id="rechazar{{ $turno->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('cuadres.rechazar', $turno) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Rechazar cuadre</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Cajero: <strong>{{ $turno->usuario?->nombre }}</strong></p>
                        <p>Diferencia: <strong>${{ number_format($turno->diferencia, 2) }}</strong></p>
                        <div class="mb-0">
                            <label class="form-label">Motivo del rechazo *</label>
                            <textarea name="motivo" class="form-control" rows="2" required placeholder="Explica el motivo"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Rechazar cuadre</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection