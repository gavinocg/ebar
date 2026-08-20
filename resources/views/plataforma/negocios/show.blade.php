@extends('layouts.sidebar')

@section('title', $negocio->nombre)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="badge bg-dark">super_admin</span>
        <h1 class="h3 mt-2">{{ $negocio->nombre }}</h1>
        <p class="text-muted mb-0">
            RUC: <strong>{{ $negocio->ruc ?: '—' }}</strong> ·
            Sucursales: <strong>{{ $negocio->sucursales->where('esta_activa', true)->count() }} activas</strong>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('plataforma.negocios.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <a href="{{ route('plataforma.negocios.edit', $negocio) }}" class="btn btn-outline-primary">
            <i class="bi bi-pencil"></i> Editar
        </a>
    </div>
</div>

@if(session('credenciales'))
    <div class="alert alert-warning border-warning">
        <h6><i class="bi bi-key"></i> Credenciales del propietario (guárdalas, solo se muestran una vez)</h6>
        <p class="mb-0">
            <strong>{{ session('credenciales')['nombre'] }}</strong> ·
            Correo: <code>{{ session('credenciales')['correo'] }}</code> ·
            Clave temporal: <code>{{ session('credenciales')['clave'] }}</code>
        </p>
        <small class="text-muted">En su primer ingreso el propietario deberá cambiar la contraseña.</small>
    </div>
@php
    session()->forget('credenciales');
@endphp
@endif

<ul class="nav nav-tabs mt-4" id="barTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">General</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="contratos-tab" data-bs-toggle="tab" data-bs-target="#contratos" type="button" role="tab">Contratos y pagos</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="propietario-tab" data-bs-toggle="tab" data-bs-target="#propietario" type="button" role="tab">Propietario</button>
    </li>
</ul>

<div class="tab-content bg-white rounded-bottom shadow-sm p-4" id="barTabContent">
    <div class="tab-pane fade show active" id="general" role="tabpanel">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small">Logo</div>
                @if ($negocio->logo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($negocio->logo) }}" alt="Logo" class="img-thumbnail" style="max-height: 80px;">
                @else
                    <span class="text-muted">—</span>
                @endif
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Estado</div>
                <span class="badge bg-{{ $negocio->esta_activo ? 'success' : 'danger' }}">
                    {{ $negocio->esta_activo ? 'Activo' : 'Suspendido' }}
                </span>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Contrato vigente</div>
                @php
                    $contratoVigente = $negocio->contratoVigente();
                @endphp
                @if ($contratoVigente)
                    <span class="badge bg-success">Activo hasta {{ $contratoVigente->fecha_fin->format('d/m/Y') }}</span>
                @else
                    <span class="badge bg-secondary">Sin contrato vigente</span>
                @endif
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Valor del contrato</div>
                <span class="fw-semibold">${{ number_format($contratoVigente?->valor ?? 0, 2) }}</span>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Sucursales contratadas</div>
                <span>{{ $contratoVigente?->sucursales_ilimitadas ? 'Ilimitadas' : ($contratoVigente?->numero_sucursales_contratadas ?? '—') }}</span>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Cajeros contratados</div>
                <span>{{ $contratoVigente?->cajeros_ilimitados ? 'Ilimitados' : ($contratoVigente?->numero_cajeros_contratados ?? '—') }}</span>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="contratos" role="tabpanel">
        <form method="POST" action="{{ route('plataforma.negocios.contratos.store', $negocio) }}" class="row g-3 border rounded p-3 mb-4">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Fecha inicio *</label>
                <input type="date" name="fecha_inicio" class="form-control" value="{{ old('fecha_inicio', now()->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha fin *</label>
                <input type="date" name="fecha_fin" class="form-control" value="{{ old('fecha_fin', now()->addYear()->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Valor total (USD) *</label>
                <input type="number" name="valor" step="0.01" min="0.01" class="form-control" value="{{ old('valor') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Forma de contratación *</label>
                <select name="forma_contratacion" class="form-select">
                    @foreach (['mensual' => 'Mensual', 'trimestral' => 'Trimestral', 'semestral' => 'Semestral', 'anual' => 'Anual', 'otro' => 'Otro'] as $clave => $etiqueta)
                        <option value="{{ $clave }}" @selected(old('forma_contratacion', 'mensual') === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Sucursales contratadas (xNS) *</label>
                <input type="number" name="numero_sucursales_contratadas" min="1" max="1000" class="form-control" value="{{ old('numero_sucursales_contratadas', 1) }}" required>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="sucursales_ilimitadas" id="sucursales_ilimitadas" value="1" @checked(old('sucursales_ilimitadas'))>
                    <label class="form-check-label" for="sucursales_ilimitadas">Sucursales ilimitadas</label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Cajeros contratados (xNC) *</label>
                <input type="number" name="numero_cajeros_contratados" min="1" max="1000" class="form-control" value="{{ old('numero_cajeros_contratados', 1) }}" required>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="cajeros_ilimitados" id="cajeros_ilimitados" value="1" @checked(old('cajeros_ilimitados'))>
                    <label class="form-check-label" for="cajeros_ilimitados">Cajeros ilimitados</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Referencia</label>
                <input type="text" name="referencia" class="form-control" value="{{ old('referencia') }}">
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button class="btn btn-primary"><i class="bi bi-plus-circle"></i> Registrar contrato</button>
            </div>
        </form>

        @forelse($negocio->contratos->sortByDesc('fecha_inicio') as $contrato)
            <div class="card border shadow-sm mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Contrato </strong>
                        <span class="badge bg-primary">{{ $contrato->forma_contratacion }}</span>
                        <span class="badge bg-{{ $contrato->estado === 'activo' ? 'success' : ($contrato->estado === 'vencido' ? 'danger' : 'warning text-dark') }}">{{ ucfirst($contrato->estado) }}</span>
                    </div>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('plataforma.contratos.estado', $contrato) }}" class="d-flex gap-1">
                            @csrf
                            <select name="estado" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                @foreach (['activo', 'vencido', 'suspendido', 'cancelado'] as $estado)
                                    <option value="{{ $estado }}" @selected($contrato->estado === $estado)>{{ ucfirst($estado) }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('plataforma.contratos.destroy', $contrato) }}" onsubmit="return confirm('¿Eliminar este contrato?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3"><span class="text-muted small">Desde</span><div>{{ $contrato->fecha_inicio->format('d/m/Y') }}</div></div>
                        <div class="col-md-3"><span class="text-muted small">Hasta</span><div>{{ $contrato->fecha_fin->format('d/m/Y') }}</div></div>
                        <div class="col-md-3"><span class="text-muted small">Valor total</span><div class="fw-semibold">${{ number_format($contrato->valor, 2) }}</div></div>
                        <div class="col-md-3"><span class="text-muted small">Total pagado</span><div class="fw-semibold">${{ number_format($contrato->totalPagado(), 2) }} <span class="text-muted small">/ ${{ number_format($contrato->valor, 2) }}</span></div></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><span class="text-muted small">Sucursales</span><div>{{ $contrato->sucursales_ilimitadas ? 'Ilimitadas' : $contrato->numero_sucursales_contratadas }}</div></div>
                        <div class="col-md-3"><span class="text-muted small">Cajeros</span><div>{{ $contrato->cajeros_ilimitados ? 'Ilimitados' : $contrato->numero_cajeros_contratados }}</div></div>
                        <div class="col-md-3"><span class="text-muted small">Ref.</span><div>{{ $contrato->referencia ?: '—' }}</div></div>
                    </div>

                    <form method="POST" action="{{ route('plataforma.contratos.pagos.store', $contrato) }}" class="row g-2 border-top pt-3">
                        @csrf
                        <div class="col-md-2">
                            <label class="form-label small">Fecha pago *</label>
                            <input type="date" name="fecha_pago" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Concepto</label>
                            <input type="text" name="concepto" class="form-control form-control-sm" placeholder="Cuota mensual">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Forma pago *</label>
                            <select name="forma_pago" class="form-select form-select-sm">
                                @foreach (['efectivo', 'transferencia', 'tarjeta', 'otro'] as $forma)
                                    <option value="{{ $forma }}">{{ ucfirst($forma) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Valor (USD) *</label>
                            <input type="number" name="valor" step="0.01" min="0.01" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Referencia</label>
                            <input type="text" name="referencia" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus"></i></button>
                        </div>
                    </form>

                    @if ($contrato->pagos->isNotEmpty())
                        <table class="table table-sm table-hover mt-3">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th>Forma</th>
                                    <th class="text-end">Valor</th>
                                    <th>Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($contrato->pagos->sortByDesc('fecha_pago') as $pago)
                                    <tr class="{{ $pago->estado === 'anulado' ? 'text-muted text-decoration-line-through' : '' }}">
                                        <td>{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                                        <td>{{ $pago->concepto ?: '—' }}</td>
                                        <td>{{ ucfirst($pago->forma_pago) }}</td>
                                        <td class="text-end">${{ number_format($pago->valor, 2) }}</td>
                                        <td><span class="badge bg-{{ $pago->estado === 'registrado' ? 'success' : 'secondary' }}">{{ ucfirst($pago->estado) }}</span></td>
                                        <td class="text-end">
                                            @if ($pago->estado === 'registrado')
                                                <form method="POST" action="{{ route('plataforma.pagos.anular', $pago) }}" onsubmit="return confirm('¿Anular este pago?')">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i></button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-muted">Este bar aún no tiene contratos registrados.</p>
        @endforelse
    </div>

    <div class="tab-pane fade" id="propietario" role="tabpanel">
        @if ($propietario?->usuario)
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">Nombre</div>
                    <div class="fw-semibold">{{ $propietario->usuario->nombre }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Correo</div>
                    <div>{{ $propietario->usuario->correo }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Cédula</div>
                    <div>{{ $propietario->usuario->cedula ?: '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Celular</div>
                    <div>{{ $propietario->usuario->celular ?: '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Estado</div>
                    <span class="badge bg-{{ $propietario->usuario->esta_activo ? 'success' : 'danger' }}">{{ $propietario->usuario->esta_activo ? 'Activo' : 'Inactivo' }}</span>
                </div>
            </div>
        @else
            <p class="text-muted">No hay propietario registrado para este bar.</p>
        @endif
    </div>
</div>
@endsection