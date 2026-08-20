@extends('layouts.pos')

@push('styles')
<style>
    body {
        overflow-y: auto !important;
        height: auto !important;
        min-height: 100vh;
    }
</style>
@endpush

@section('title', 'Apertura de turno')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 70vh; padding: 16px;">
    <div class="card shadow-sm w-100" style="max-width: 380px;">
        <div class="card-body text-center p-4">
            <i class="bi bi-cash-coin display-4 text-success"></i>
            <h5 class="mt-2 mb-1">Apertura de turno</h5>
            <p class="text-muted small mb-1">{{ auth()->user()->nombre }}</p>
            <p class="text-muted small mb-3">Registra el fondo inicial con el que inicias tu turno.</p>

            @if($errors->any())
                <div class="alert alert-danger py-2">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('caja.abrir') }}">
                @csrf
                <div class="mb-3 text-start">
                    <label class="form-label" for="fondo_inicial">Fondo inicial ($)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" id="fondo_inicial" name="fondo_inicial" class="form-control form-control-lg text-center" step="0.01" min="0" max="99999999.99" value="0.00" required autofocus>
                    </div>
                    <div class="form-text">Efectivo que recibes para dar cambio.</div>
                </div>
                <button type="submit" class="btn btn-success w-100 btn-lg">
                    <i class="bi bi-unlock"></i> Abrir turno y comenzar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection