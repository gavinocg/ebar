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
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Ej: Impresora Cocina">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo de Impresora</label>
                        <select name="tipo_conexion" class="form-select" id="connectionType" required onchange="toggleFields()">
                            <option value="bluetooth">POS - Bluetooth</option>
                            <option value="wifi">POS - WiFi</option>
                            <option value="lan">POS - LAN</option>
                            <option value="normal">Convencional - Inyección/Láser (A5)</option>
                        </select>
                        <small class="text-muted">POS: Impresoras térmicas de tickets | Normal: Impresoras de inyección o láser</small>
                    </div>
                    
                    <div id="networkFields">
                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="direccion" class="form-control" placeholder="MAC o IP">
                            <small class="text-muted">Bluetooth: 00:11:22:33:44:55 | WiFi/LAN: 192.168.1.100</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Puerto</label>
                            <input type="number" name="puerto" class="form-control" value="9100">
                            <small class="text-muted">Estándar: 9100 para impresoras POS de red</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tamaño de Papel</label>
                        <select name="ancho_papel" class="form-select" required>
                            <option value="80mm">80mm (POS térmica)</option>
                            <option value="58mm">58mm (POS térmica)</option>
                            <option value="a5">A5 (148mm x 210mm)</option>
                            <option value="a4">A4 (210mm x 297mm)</option>
                            <option value="letter">Carta (216mm x 279mm)</option>
                        </select>
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
                                    <th>Tipo</th>
                                    <th>Dirección</th>
                                    <th>Puerto</th>
                                    <th>Papel</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($printers as $printer)
                                    <tr>
                                        <td>
                                            {{ $printer->nombre }}
                                            @if($printer->es_predeterminada)
                                                <span class="badge bg-success">Principal</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($printer->tipo_conexion == 'bluetooth')
                                                <i class="bi bi-bluetooth text-primary"></i> Bluetooth
                                            @elseif($printer->tipo_conexion == 'wifi')
                                                <i class="bi bi-wifi text-success"></i> WiFi
                                            @else
                                                <i class="bi bi-ethernet text-info"></i> LAN
                                            @endif
                                        </td>
                                        <td><code>{{ $printer->direccion }}</code></td>
                                        <td>{{ $printer->puerto }}</td>
                                        <td>{{ $printer->ancho_papel }}</td>
                                        <td>
                                            @if($printer->esta_activa)
                                                <span class="badge bg-success">Activa</span>
                                            @else
                                                <span class="badge bg-secondary">Inactiva</span>
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
                                                            <label class="form-label">Tipo de Conexión</label>
                                                            <select name="tipo_conexion" class="form-select" required>
                                                                <option value="bluetooth" {{ $printer->tipo_conexion == 'bluetooth' ? 'selected' : '' }}>Bluetooth</option>
                                                                <option value="wifi" {{ $printer->tipo_conexion == 'wifi' ? 'selected' : '' }}>WiFi</option>
                                                                <option value="lan" {{ $printer->tipo_conexion == 'lan' ? 'selected' : '' }}>LAN</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Dirección</label>
                                                            <input type="text" name="direccion" class="form-control" value="{{ $printer->direccion }}" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Puerto</label>
                                                            <input type="number" name="puerto" class="form-control" value="{{ $printer->puerto }}" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Tamaño de Papel</label>
                                                            <select name="ancho_papel" class="form-select" required>
                                                                <option value="80mm" {{ $printer->ancho_papel == '80mm' ? 'selected' : '' }}>80mm (POS térmica)</option>
                                                                <option value="58mm" {{ $printer->ancho_papel == '58mm' ? 'selected' : '' }}>58mm (POS térmica)</option>
                                                                <option value="a5" {{ $printer->ancho_papel == 'a5' ? 'selected' : '' }}>A5 (148mm x 210mm)</option>
                                                                <option value="a4" {{ $printer->ancho_papel == 'a4' ? 'selected' : '' }}>A4 (210mm x 297mm)</option>
                                                                <option value="letter" {{ $printer->ancho_papel == 'letter' ? 'selected' : '' }}>Carta (216mm x 279mm)</option>
                                                            </select>
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
function toggleFields() {
    const type = document.getElementById('connectionType').value;
    const networkFields = document.getElementById('networkFields');
    
    if (type === 'normal') {
        networkFields.style.display = 'none';
    } else {
        networkFields.style.display = 'block';
    }
}

if (document.getElementById('connectionType')) {
    toggleFields();
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
                    await printTestTicket(data.ticket, data.printer);
                } else if (data.type === 'normal') {
                    printTestTicketHtml(data.ticket_html);
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

async function printTestTicket(ticketBase64, printerData) {
    try {
        const commands = atob(ticketBase64);
        
        if (printerData.tipo === 'bluetooth') {
            await printViaBluetooth(commands, printerData.direccion);
        } else if (printerData.tipo === 'wifi' || printerData.tipo === 'lan') {
            await printViaNetwork(commands, printerData.direccion, printerData.puerto);
        }
    } catch (error) {
        console.error('Error de impresión:', error);
        showPrintFallback(ticketBase64);
    }
}

function printTestTicketHtml(htmlContent) {
    let iframe = document.getElementById('print-iframe');
    if (iframe) {
        iframe.remove();
    }
    
    iframe = document.createElement('iframe');
    iframe.id = 'print-iframe';
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    document.body.appendChild(iframe);
    
    const doc = iframe.contentWindow.document;
    doc.open();
    doc.write(htmlContent);
    doc.close();
    
    setTimeout(() => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        setTimeout(() => {
            iframe.remove();
        }, 1000);
    }, 500);
}

async function printViaBluetooth(commands, macAddress) {
    if (!bluetoothCharacteristic) {
        throw new Error('Conecta la impresora Bluetooth antes de probarla.');
    }
    
    const data = Uint8Array.from(commands, character => character.charCodeAt(0));
    
    const chunkSize = 512;
    for (let i = 0; i < data.length; i += chunkSize) {
        const chunk = data.slice(i, i + chunkSize);
        await bluetoothCharacteristic.writeValue(chunk);
    }
}

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

async function printViaNetwork(commands, ip, port) {
    await fetch(`http://${ip}:${port}`, {
        method: 'POST',
        body: commands,
        mode: 'no-cors'
    });
}

function showPrintFallback(ticketBase64) {
    const ticket = atob(ticketBase64);
    const htmlContent = `
        <html>
        <head>
            <title>Ticket de Prueba</title>
            <style>
                body { font-family: monospace; width: 80mm; margin: 0 auto; padding: 5mm; }
                pre { white-space: pre-wrap; word-wrap: break-word; }
            </style>
        </head>
        <body>
            <pre>${ticket}</pre>
        </body>
        </html>
    `;
    printTestTicketHtml(htmlContent);
}
</script>
@endpush
@endsection
