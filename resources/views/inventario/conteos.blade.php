@extends('layouts.sidebar')

@section('title', 'Conteos físicos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Conteos físicos</h4>
    <a href="{{ route('conteos.crear') }}" class="btn btn-primary"><i class="bi bi-clipboard-check"></i> Nuevo conteo</a>
</div>

<div class="card">
    <div class="card-body">
        @if($conteos->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Fecha</th>
                            <th>Artículos</th>
                            <th>Responsable</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($conteos as $conteo)
                            <tr>
                                <td class="fw-semibold">{{ $conteo->numero }}</td>
                                <td>{{ $conteo->fecha->format('d/m/Y') }}</td>
                                <td>{{ $conteo->detalles_count }}</td>
                                <td>{{ $conteo->usuario?->nombre ?? '—' }}</td>
                                <td>
                                    @if($conteo->estado === 'aplicado')
                                        <span class="badge bg-success">Aplicado</span>
                                    @else
                                        <span class="badge bg-warning">{{ ucfirst($conteo->estado) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($conteo->estado !== 'aplicado')
                                        <form action="{{ route('conteos.aplicar', $conteo) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Aplicar diferencias a las existencias?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success"><i class="bi bi-check2-circle"></i> Aplicar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted text-center">No hay conteos registrados.</p>
        @endif
    </div>
</div>
@endsection