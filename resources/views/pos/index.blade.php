@extends('layouts.pos')

@section('title', 'Punto de Venta')

@section('content')
<div class="pos-container">
    <div class="category-sidebar">
        <button class="category-btn active" data-category="all">
            <i class="bi bi-grid"></i>
            <span>Todos</span>
        </button>
        @foreach($categories as $category)
            <button class="category-btn" data-category="{{ $category->id }}" style="background-color: {{ $category->color }}">
                @if($category->imagen_path)
                    <img src="{{ asset('storage/' . $category->imagen_path) }}" alt="" class="category-image">
                @else
                    <i class="{{ $category->icono ?: 'bi bi-tag' }}"></i>
                @endif
                <span>{{ $category->nombre }}</span>
            </button>
        @endforeach
    </div>

    <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
        <div class="search-bar">
            <input type="text" id="searchInput" class="form-control" placeholder="Buscar producto o escanear código...">
        </div>
        
        <div class="products-area">
            <div class="row g-3" id="productsGrid">
                @foreach($products as $product)
                    <div class="col-6 col-md-4 col-lg-3 product-item" 
                         data-category="{{ $product->categoria_id }}"
                         data-name="{{ strtolower($product->nombre) }}"
                         data-barcode="{{ $product->codigo_barras }}">
                        <div class="product-card {{ $product->existencias == 0 ? 'out-of-stock' : '' }}"
                             data-id="{{ $product->id }}"
                              data-name="{{ $product->nombre }}"
                              data-price="{{ $product->precio }}"
                              data-stock="{{ $product->existencias }}"
                              style="background-color: {{ $product->color ?: '#ffffff' }}">
                            @if($product->distintivo)
                                <span class="product-badge" style="background-color: {{ $product->distintivo_color ?: '#16a34a' }}">{{ $product->distintivo }}</span>
                            @endif
                            @if($product->imagen_path)
                                <img src="{{ asset('storage/' . $product->imagen_path) }}" alt="{{ $product->nombre }}" class="product-image">
                            @else
                                <span class="product-fallback"><i class="bi bi-bag"></i></span>
                            @endif
                            <div class="name">{{ $product->nombre }}</div>
                            <div class="price">${{ number_format($product->precio, 2) }}</div>
                            <div class="stock">Existencias: {{ $product->existencias }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if($products->count() == 0)
                <div class="text-center text-muted mt-5">
                    <i class="bi bi-box" style="font-size:48px"></i>
                    <p class="mt-3">No hay productos. Ve a Admin para agregar productos.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="cart-panel" id="cartPanel">
        <div class="cart-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-cart"></i> Ticket</h5>
            <button class="btn btn-sm btn-outline-light d-md-none" onclick="toggleCart()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <div class="cart-items" id="cartItems">
            <div class="empty-cart" id="emptyCart">
                <i class="bi bi-cart-x"></i>
                <p>Carrito vacío</p>
            </div>
        </div>
        
        <div class="cart-footer">
            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal:</span>
                <span id="subtotal">$0.00</span>
            </div>
            @if($business->cobrar_impuesto)
            <div class="d-flex justify-content-between mb-2" id="taxRow">
                <span id="taxLabel">Impuesto ({{ $business->porcentaje_impuesto }}%):</span>
                <span id="tax">$0.00</span>
            </div>
            @endif
            <div class="cart-total">
                Total: <span id="total">$0.00</span>
            </div>
            
            <div class="d-flex gap-2 mb-2">
                <select id="paymentMethod" class="form-select">
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </div>
            
            <button class="checkout-btn" id="checkoutBtn" disabled onclick="checkout()">
                <i class="bi bi-check-circle"></i> COBRAR
            </button>
            <button class="btn btn-outline-danger w-100 mt-2" onclick="clearCart()">
                <i class="bi bi-trash"></i> Vaciar
            </button>
        </div>
    </div>
</div>

<button class="cart-toggle d-md-none" onclick="toggleCart()">
    <i class="bi bi-cart"></i>
    <span class="cart-badge" id="cartBadge" style="display:none">0</span>
</button>

<div class="modal fade" id="checkoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Cobrar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <h2 class="text-success" id="modalTotal">$0.00</h2>
                    <small class="text-muted">Total a cobrar</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Monto recibido</label>
                    <input type="number" id="paidAmount" class="form-control form-control-lg text-center" step="0.01" min="0">
                </div>
                
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button class="btn btn-outline-primary quick-amount" data-amount="exacto">Exacto</button>
                    <button class="btn btn-outline-primary quick-amount" data-amount="50">$50</button>
                    <button class="btn btn-outline-primary quick-amount" data-amount="100">$100</button>
                    <button class="btn btn-outline-primary quick-amount" data-amount="200">$200</button>
                    <button class="btn btn-outline-primary quick-amount" data-amount="500">$500</button>
                </div>
                
                <div class="text-center">
                    <span class="text-muted">Cambio:</span>
                    <h3 class="text-primary" id="changeAmount">$0.00</h3>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success btn-lg" onclick="processSale()">
                    <i class="bi bi-printer"></i> Cobrar e Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let cart = [];
let currentTotal = 0;
let lastSaleHtml = null;
const printerConfig = {!! $printer ? json_encode([
    'tipo' => $printer->tipo_conexion,
    'direccion' => $printer->direccion,
    'puerto' => $printer->puerto,
]) : 'null' !!};
@php
    $business = \App\Models\ConfiguracionNegocio::obtenerConfiguracion();
@endphp
const chargeTax = {{ $business->cobrar_impuesto ? 'true' : 'false' }};
const taxPercentage = {{ $business->porcentaje_impuesto }};

document.querySelectorAll('.category-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const cat = this.dataset.category;
        document.querySelectorAll('.product-item').forEach(item => {
            if (cat === 'all' || item.dataset.category === cat) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
});

document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', function() {
        if (this.classList.contains('out-of-stock')) return;
        
        const id = this.dataset.id;
        const name = this.dataset.name;
        const price = parseFloat(this.dataset.price);
        const stock = parseInt(this.dataset.stock);
        
        const existing = cart.find(item => item.id === id);
        if (existing) {
            if (existing.qty >= stock) {
                alert('No hay más stock disponible');
                return;
            }
            existing.qty++;
        } else {
            cart.push({ id, name, price, qty: 1, stock });
        }
        
        renderCart();
    });
});

document.getElementById('searchInput').addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    document.querySelectorAll('.product-item').forEach(item => {
        const name = item.dataset.name;
        const barcode = item.dataset.barcode || '';
        if (name.includes(query) || barcode.includes(query)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});

function renderCart() {
    const container = document.getElementById('cartItems');
    const emptyCart = document.getElementById('emptyCart');
    
    if (cart.length === 0) {
        container.innerHTML = '<div class="empty-cart"><i class="bi bi-cart-x"></i><p>Carrito vacío</p></div>';
        document.getElementById('checkoutBtn').disabled = true;
        updateTotals();
        return;
    }
    
    let html = '';
    cart.forEach((item, index) => {
        html += `
            <div class="cart-item">
                <div class="qty-controls">
                    <button class="qty-btn" onclick="changeQty(${index}, -1)">-</button>
                    <span class="qty">${item.qty}</span>
                    <button class="qty-btn" onclick="changeQty(${index}, 1)">+</button>
                </div>
                <div class="info">
                    <div class="name">${item.name}</div>
                    <div class="price">$${item.price.toFixed(2)} c/u</div>
                </div>
                <div class="text-end">
                    <div class="fw-bold">$${(item.price * item.qty).toFixed(2)}</div>
                    <button class="remove-btn" onclick="removeItem(${index})">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    document.getElementById('checkoutBtn').disabled = false;
    updateTotals();
}

function changeQty(index, delta) {
    const item = cart[index];
    const newQty = item.qty + delta;
    
    if (newQty <= 0) {
        cart.splice(index, 1);
    } else if (newQty > item.stock) {
        alert('No hay más stock disponible');
        return;
    } else {
        item.qty = newQty;
    }
    
    renderCart();
}

function removeItem(index) {
    cart.splice(index, 1);
    renderCart();
}

function clearCart() {
    if (cart.length === 0) return;
    if (confirm('¿Vaciar el carrito?')) {
        cart = [];
        renderCart();
    }
}

function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    const tax = chargeTax ? (subtotal * (taxPercentage / 100)) : 0;
    const total = subtotal + tax;
    currentTotal = total;
    
    document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
    
    const taxRow = document.getElementById('taxRow');
    const taxLabel = document.getElementById('taxLabel');
    if (chargeTax && taxRow && taxLabel) {
        taxRow.style.display = '';
        taxLabel.textContent = 'IVA (' + taxPercentage + '%):';
        document.getElementById('tax').textContent = '$' + tax.toFixed(2);
    }
    
    document.getElementById('total').textContent = '$' + total.toFixed(2);
    
    const badge = document.getElementById('cartBadge');
    const count = cart.reduce((sum, item) => sum + item.qty, 0);
    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

function checkout() {
    if (cart.length === 0) return;
    
    document.getElementById('modalTotal').textContent = '$' + currentTotal.toFixed(2);
    document.getElementById('paidAmount').value = currentTotal.toFixed(2);
    document.getElementById('changeAmount').textContent = '$0.00';
    
    const modal = new bootstrap.Modal(document.getElementById('checkoutModal'));
    modal.show();
}

document.getElementById('paidAmount').addEventListener('input', function() {
    const paid = parseFloat(this.value) || 0;
    const change = paid - currentTotal;
    document.getElementById('changeAmount').textContent = '$' + Math.max(0, change).toFixed(2);
});

document.querySelectorAll('.quick-amount').forEach(btn => {
    btn.addEventListener('click', function() {
        const amount = this.dataset.amount;
        const input = document.getElementById('paidAmount');
        if (amount === 'exacto') {
            input.value = currentTotal.toFixed(2);
        } else {
            input.value = amount;
        }
        input.dispatchEvent(new Event('input'));
    });
});

async function processSale() {
    const paid = parseFloat(document.getElementById('paidAmount').value) || 0;
    
    if (paid < currentTotal) {
        alert('El monto recibido es insuficiente');
        return;
    }
    
    const btn = document.querySelector('#checkoutModal button[onclick="processSale()"]');
    const idempotencyKey = crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';
    
    try {
        const response = await fetch('{{ route("punto_venta.cobrar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                items: cart.map(item => ({ producto_id: item.id, cantidad: item.qty })),
                metodo_pago: document.getElementById('paymentMethod').value,
                pagado: paid.toFixed(2),
                clave_idempotencia: idempotencyKey
            })
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `El servidor respondió ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('checkoutModal')).hide();
            
            if (data.ticket_html) {
                lastSaleHtml = data.ticket_html;
            }
            
            if (data.type === 'thermal' && data.ticket && data.printer) {
                await printTicket(data.ticket, data.printer);
            } else if (data.type === 'normal' && data.ticket_html) {
                printTicketHtml(data.ticket_html);
            }
            
            alert('Venta registrada: ' + data.sale.numero_comprobante);
            cart = [];
            renderCart();
        } else {
            throw new Error(data.message || 'No se pudo registrar la venta.');
        }
    } catch (error) {
        alert('Error al procesar la venta: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-printer"></i> Cobrar e Imprimir';
    }
}

async function printTicket(ticketBase64, printerData) {
    try {
        const commands = atob(ticketBase64);
        
        if (printerData.tipo === 'bluetooth') {
            await printViaBluetooth(commands, printerData.direccion);
        } else if (printerData.tipo === 'wifi' || printerData.tipo === 'lan') {
            await printViaNetwork(commands, printerData.direccion, printerData.puerto);
        }
    } catch (error) {
        console.error('Error de impresión:', error);
        if (lastSaleHtml) {
            printTicketHtml(lastSaleHtml);
        } else {
            showPrintFallback(ticketBase64);
        }
    }
}

async function printViaBluetooth(commands, macAddress) {
    if (!navigator.bluetooth) {
        throw new Error('Web Bluetooth no soportado en este navegador');
    }
    
    const device = await navigator.bluetooth.requestDevice({
        filters: [{ services: ['000018f0-0000-1000-8000-00805f9b34fb'] }]
    });
    
    const server = await device.gatt.connect();
    const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
    const characteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');
    
    const data = Uint8Array.from(commands, character => character.charCodeAt(0));
    
    const chunkSize = 512;
    for (let i = 0; i < data.length; i += chunkSize) {
        const chunk = data.slice(i, i + chunkSize);
        await characteristic.writeValue(chunk);
    }
}

async function printViaNetwork(commands, ip, port) {
    const response = await fetch(`http://${ip}:${port}`, {
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
            <title>Ticket</title>
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
    printTicketHtml(htmlContent);
}

function isIOS() {
    return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
}

function isSafari() {
    return /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
}

function printTicketHtml(htmlContent) {
    if (isIOS() || isSafari()) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(htmlContent);
        printWindow.document.close();
        setTimeout(() => {
            printWindow.focus();
            printWindow.print();
        }, 500);
    } else {
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
}

function toggleCart() {
    document.getElementById('cartPanel').classList.toggle('show');
}
</script>
@endpush
