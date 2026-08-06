@extends('layouts.sidebar')

@section('title', 'Reembolsos y devoluciones')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Reembolsos y devoluciones</h5>
        <a href="{{ route('ventas.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-receipt"></i> Ventas</a>
    </div>
    <div class="card-body">
        @if($reembolsos->isEmpty())
            <p class="text-muted text-center py-4">Aún no hay reembolsos registrados.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Comprobante</th>
                            <th>Tipo</th>
                            <th>Monto</th>
                            <th>Motivo</th>
                            <th>Procesado por</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reembolsos as $reembolso)
                            <tr>
                                <td>{{ $reembolso->venta ? $reembolso->venta->numero_comprobante : '—' }}</td>
                                <td>
                                    @if($reembolso->tipo === 'total')
                                        <span class="badge bg-danger">Total</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Parcial</span>
                                    @endif
                                </td>
                                <td class="text-end">${{ number_format($reembolso->monto, 2) }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($reembolso->motivo, 40) }}</td>
                                <td>{{ $reembolso->usuario?->nombre }}</td>
                                <td>{{ $reembolso->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $reembolsos->links() }}
        @endif
    </div>
</div>
@endsection