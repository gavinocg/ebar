@extends('layouts.sidebar')

@section('title', 'Ventas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Historial de Ventas</h4>
</div>

<div class="card">
    <div class="card-body">
        @if($sales->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Fecha</th>
                            <th>Artículos</th>
                            <th>Total</th>
                            <th>Pago</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sales as $sale)
                            <tr>
                                <td><strong>{{ $sale->numero_comprobante }}</strong></td>
                                <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                <td><span class="badge bg-info">{{ $sale->items_count }}</span></td>
                                <td><strong>${{ number_format($sale->total, 2) }}</strong></td>
                                <td>
                                    @if($sale->metodo_pago == 'efectivo')
                                        <span class="badge bg-success">Efectivo</span>
                                    @elseif($sale->metodo_pago == 'credito')
                                        <span class="badge bg-warning text-dark">Crédito</span>
                                    @elseif($sale->metodo_pago == 'transferencia')
                                        <span class="badge bg-info">Transferencia</span>
                                    @elseif($sale->metodo_pago == 'dividido')
                                        <span class="badge bg-primary">Dividido</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($sale->metodo_pago) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('ventas.show', $sale) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center">
                {{ $sales->links() }}
            </div>
        @else
            <p class="text-muted text-center">No hay ventas registradas</p>
        @endif
    </div>
</div>
@endsection
