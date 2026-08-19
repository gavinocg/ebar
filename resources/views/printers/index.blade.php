@extends('layouts.sidebar')

@section('title', 'Impresoras')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Nueva Impresora</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('impresoras.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="tipo_conexion" value="bluetooth">
                    <input type="hidden" name="ancho_papel" value="58mm">

                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Ej: Impresora Cocina">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sucursal</label>
                        <select name="sucursal_id" class="form-select">
                            <option value="">Todas las sucursales</option>
                            @foreach($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Conexión</label>
                        <div><span class="badge bg-primary"><i class="bi bi-bluetooth"></i> Bluetooth</span></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Papel</label>
                        <div><span class="badge bg-secondary">Térmica 58 mm</span></div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" name="es_predeterminada" class="form-check-input" value="1">
                        <label class="form-check-label">Impresora principal</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle"></i> Agregar Impresora
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Impresoras Configuradas</h5>
            </div>
            <div class="card-body">
                @if($printers->count() > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Sucursal</th>
                                    <th>Conexión</th>
                                    <th>Papel</th>
                                    <th>Estado</th>
                                    <th>Predeterminada</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($printers as $printer)
                                    <tr>
                                        <td>{{ $printer->nombre }}</td>
                                        <td>{{ $printer->sucursal?->nombre ?? 'Todas' }}</td>
                                        <td>
                                            <span class="badge bg-primary"><i class="bi bi-bluetooth"></i> Bluetooth</span>
                                        </td>
                                        <td><code>58mm</code></td>
                                        <td>
                                            @if($printer->esta_activa)
                                                <span class="badge bg-success">Activa</span>
                                            @else
                                                <span class="badge bg-secondary">Inactiva</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($printer->es_predeterminada)
                                                <span class="badge bg-success">Principal</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-success test-printer-btn" data-printer-id="{{ $printer->id }}" data-connection-type="{{ $printer->tipo_conexion }}">
                                                <i class="bi bi-printer"></i> Probar
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $printer->id }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="{{ route('impresoras.destroy', $printer) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar impresora?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="editModal{{ $printer->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('impresoras.update', $printer) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="tipo_conexion" value="bluetooth">
                                                    <input type="hidden" name="ancho_papel" value="58mm">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Editar Impresora</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Nombre</label>
                                                            <input type="text" name="nombre" class="form-control" value="{{ $printer->nombre }}" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Sucursal</label>
                                                            <select name="sucursal_id" class="form-select">
                                                                <option value="">Todas las sucursales</option>
                                                                @foreach($sucursales as $sucursal)
                                                                    <option value="{{ $sucursal->id }}" @selected($printer->sucursal_id === $sucursal->id)>{{ $sucursal->nombre }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Conexión</label>
                                                            <div><span class="badge bg-primary"><i class="bi bi-bluetooth"></i> Bluetooth</span></div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Papel</label>
                                                            <div><span class="badge bg-secondary">Térmica 58 mm</span></div>
                                                        </div>

                                                        <div class="mb-3 form-check">
                                                            <input type="checkbox" name="es_predeterminada" class="form-check-input" value="1" {{ $printer->es_predeterminada ? 'checked' : '' }}>
                                                            <label class="form-check-label">Impresora principal</label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary">Guardar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center">No hay impresoras configuradas</p>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let bluetoothDevice = null;
let bluetoothCharacteristic = null;
const bluetoothServiceUuid = '000018f0-0000-1000-8000-00805f9b34fb';
const bluetoothCharacteristicUuid = '00002af1-0000-1000-8000-00805f9b34fb';

async function conectarImpresoraBluetooth() {
    if (!navigator.bluetooth) {
        throw new Error('Web Bluetooth no está disponible en este navegador.');
    }

    bluetoothDevice = await navigator.bluetooth.requestDevice({
        acceptAllDevices: true,
        optionalServices: [bluetoothServiceUuid]
    });
    const server = await bluetoothDevice.gatt.connect();
    const service = await server.getPrimaryService(bluetoothServiceUuid);
    bluetoothCharacteristic = await service.getCharacteristic(bluetoothCharacteristicUuid);
}

document.querySelectorAll('.test-printer-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const printerId = this.dataset.printerId;
        const originalHtml = this.innerHTML;

        this.disabled = true;
        this.innerHTML = '<i class="bi bi-hourglass-split"></i> Probando...';

        try {
            if (this.dataset.connectionType === 'bluetooth') {
                await conectarImpresoraBluetooth();
            }

            const response = await fetch(`{{ url('/impresoras') }}/${printerId}/probar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`El servidor respondió ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                if (data.type === 'thermal') {
                    try {
                        await printTestTicket(data.ticket, data.datos);
                    } catch (printError) {
                        console.warn(printError);
                        showPrintFallback(data.ticket);
                    }
                }
                alert('Ticket de prueba enviado correctamente');
            } else {
                alert('Error: ' + (data.message || 'Error desconocido'));
            }
        } catch (error) {
            alert('Error al probar la impresora: ' + error.message);
        } finally {
            this.disabled = false;
            this.innerHTML = originalHtml;
        }
    });
});

async function printTestTicket(ticketBase64, connectionData) {
    const commands = atob(ticketBase64);

    if (connectionData && connectionData.tipo === 'bluetooth') {
        await printViaBluetooth(commands);
    } else {
        throw new Error('La impresora predeterminada no tiene una conexión compatible.');
    }
}

async function printViaBluetooth(commands) {
    if (!bluetoothCharacteristic) {
        throw new Error('Conecta la impresora Bluetooth antes de probarla.');
    }

    const data = Uint8Array.from(commands, character => character.charCodeAt(0));

    const chunkSize = 20;
    for (let i = 0; i < data.length; i += chunkSize) {
        const chunk = data.slice(i, i + chunkSize);
        await bluetoothCharacteristic.writeValue(chunk);
        await new Promise(resolve => setTimeout(resolve, 10));
    }
}

function showPrintFallback(ticketBase64) {
    const ticket = atob(ticketBase64);
    const htmlContent = `
        <html>
        <head>
            <title>Ticket de Prueba</title>
            <style>
                body { font-family: monospace; width: 58mm; margin: 0 auto; padding: 5mm; }
                pre { white-space: pre-wrap; word-wrap: break-word; }
            </style>
        </head>
        <body>
            <pre>${ticket}</pre>
        </body>
        </html>
    `;

    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        throw new Error('El navegador bloqueó la ventana de impresión. Permite ventanas emergentes para este sitio.');
    }

    printWindow.document.open();
    printWindow.document.write(htmlContent);
    printWindow.document.close();
    setTimeout(() => {
        printWindow.focus();
        printWindow.print();
        setTimeout(() => printWindow.close(), 1000);
    }, 500);
}
</script>
@endpush
@endsection