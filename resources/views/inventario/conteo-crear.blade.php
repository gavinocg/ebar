@extends('layouts.sidebar')

@section('title', 'Nuevo conteo físico')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Nuevo conteo físico</h4>
    <a href="{{ route('conteos.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<form method="POST" action="{{ route('conteos.store') }}">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Notas</label>
                <input type="text" name="notas" class="form-control">
            </div>
            <p class="text-muted">Registra la existencia real de cada producto. Las diferencias se aplicarán al guardar.</p>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-end">En sistema</th>
                            <th>Existencia real</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productos as $producto)
                            <tr>
                                <td class="fw-semibold">{{ $producto->nombre }}</td>
                                <td class="text-end">{{ $producto->existencias }}</td>
                                <td style="width:180px">
                                    <input type="hidden" name="productos[{{ $loop->index }}][producto_id]" value="{{ $producto->id }}">
                                    <input type="number" name="productos[{{ $loop->index }}][existencias_reales]" class="form-control" min="0" value="{{ $producto->existencias }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end">
                <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar conteo</button>
            </div>
        </div>
    </div>
</form>
@endsection