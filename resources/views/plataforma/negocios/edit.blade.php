@extends('layouts.sidebar')

@section('title', 'Editar bar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="badge bg-dark">super_admin</span>
        <h1 class="h3 mt-2">{{ $negocio->nombre }}</h1>
    </div>
    <a href="{{ route('plataforma.negocios.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<ul class="nav nav-tabs mt-4" id="barTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">General</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="sucursales-tab" data-bs-toggle="tab" data-bs-target="#sucursales" type="button" role="tab">Sucursales</button>
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
        <h5 class="mb-3"><i class="bi bi-shop"></i> Datos Bar</h5>

        <form method="POST" action="{{ route('plataforma.negocios.update', $negocio) }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            @method('PUT')

            <div class="col-md-6">
                <label class="form-label">Nombre del bar *</label>
                <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $negocio->nombre) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">RUC</label>
                <input type="text" name="ruc" class="form-control" value="{{ old('ruc', $negocio->ruc) }}" maxlength="13">
                <div class="form-text">13 dígitos, se valida su dígito verificador.</div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Logo</label>
                <input type="file" name="logo" class="form-control" accept="image/*">
                @if ($negocio->logo)
                    <div class="form-text">Actual: <img src="{{ \Illuminate\Support\Facades\Storage::url($negocio->logo) }}" alt="Logo" class="img-thumbnail" style="max-height: 28px;"></div>
                @endif
            </div>

            <div class="col-md-4">
                <label class="form-label">Zona horaria *</label>
                <select name="zona_horaria" class="form-select">
                    @foreach ($zonasHorarias as $clave => $etiqueta)
                        <option value="{{ $clave }}" @selected(old('zona_horaria', $negocio->zona_horaria) === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Moneda *</label>
                <select name="moneda" class="form-select">
                    @foreach ($monedas as $clave => $etiqueta)
                        <option value="{{ $clave }}" @selected(old('moneda', $negocio->moneda) === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <div class="form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" name="esta_activo" id="esta_activo" value="1" @checked(old('esta_activo', $negocio->esta_activo))>
                    <label class="form-check-label" for="esta_activo">Bar activo</label>
                </div>
            </div>

            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Guardar
                </button>
            </div>
        </form>
    </div>

    <div class="tab-pane fade" id="sucursales" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="bi bi-buildings"></i> Sucursales</h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#crearSucursal">
                <i class="bi bi-plus-circle"></i> Nueva sucursal
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle bg-white shadow-sm rounded">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Ubicación</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($negocio->sucursales as $sucursal)
                        <tr>
                            <td class="fw-semibold">{{ $sucursal->nombre }}</td>
                            <td>
                                @if ($sucursal->provincia || $sucursal->canton || $sucursal->ciudad)
                                    {{ collect([$sucursal->ciudad, $sucursal->canton, $sucursal->provincia])->filter()->implode(', ') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $sucursal->direccion ?? '—' }}</td>
                            <td>{{ $sucursal->telefono ?? '—' }}</td>
                            <td>
                                @if ($sucursal->esta_activa)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editar{{ $sucursal->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('plataforma.sucursales.destroy', $sucursal->id) }}" class="d-inline" onsubmit="return confirm('¿Eliminar esta sucursal?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No hay sucursales registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="modal fade" id="crearSucursal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('plataforma.negocios.sucursales.store', $negocio) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Nueva sucursal</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="direccion" class="form-control">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Provincia</label>
                                    <input type="text" name="provincia" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Cantón</label>
                                    <input type="text" name="canton" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ciudad</label>
                                    <input type="text" name="ciudad" class="form-control">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @foreach ($negocio->sucursales as $sucursal)
            <div class="modal fade" id="editar{{ $sucursal->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('plataforma.sucursales.update', $sucursal->id) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">Editar sucursal</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" name="nombre" class="form-control" value="{{ $sucursal->nombre }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Dirección</label>
                                    <input type="text" name="direccion" class="form-control" value="{{ $sucursal->direccion }}">
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Provincia</label>
                                        <input type="text" name="provincia" class="form-control" value="{{ $sucursal->provincia }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Cantón</label>
                                        <input type="text" name="canton" class="form-control" value="{{ $sucursal->canton }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Ciudad</label>
                                        <input type="text" name="ciudad" class="form-control" value="{{ $sucursal->ciudad }}">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" name="telefono" class="form-control" value="{{ $sucursal->telefono }}">
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="esta_activa" id="activa{{ $sucursal->id }}" value="1" @checked($sucursal->esta_activa)>
                                    <label class="form-check-label" for="activa{{ $sucursal->id }}">Activa</label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
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
        <h5 class="mb-3"><i class="bi bi-person-badge"></i> Datos del propietario</h5>

        @if ($propietario?->usuario)
            <form method="POST" action="{{ route('plataforma.negocios.propietario.update', $negocio) }}" class="row g-3">
                @csrf

                <div class="col-md-6">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $propietario->usuario->nombre) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Correo *</label>
                    <input type="email" name="correo" class="form-control" value="{{ old('correo', $propietario->usuario->correo) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Cédula</label>
                    <input type="text" name="cedula" class="form-control" value="{{ old('cedula', $propietario->usuario->cedula) }}" maxlength="10">
                    <div class="form-text">10 dígitos, se valida su dígito verificador.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Celular</label>
                    <input type="text" name="celular" class="form-control" value="{{ old('celular', $propietario->usuario->celular) }}">
                </div>

                <div class="col-md-4">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="esta_activo" id="propietario_activo" value="1" @checked(old('esta_activo', $propietario->usuario->esta_activo))>
                        <label class="form-check-label" for="propietario_activo">Propietario activo</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Nueva contraseña (opcional)</label>
                    <input type="password" name="clave" class="form-control">
                    <div class="form-text">Déjala en blanco para mantener la actual.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Confirmar contraseña</label>
                    <input type="password" name="clave_confirmation" class="form-control">
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Guardar
                    </button>
                </div>
            </form>
        @else
            <p class="text-muted">No hay propietario registrado para este bar.</p>
        @endif
    </div>
</div>
@endsection