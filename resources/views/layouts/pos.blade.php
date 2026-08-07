@php
    $business = \App\Models\ConfiguracionNegocio::obtenerConfiguracion();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title', 'TPV') - {{ $business->nombre_negocio }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 100px;
            --cart-width: 350px;
        }
        
        * {
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            overflow: hidden;
            height: 100vh;
            background: #f8f9fa;
        }
        
        .pos-container {
            display: flex;
            height: calc(100vh - 56px);
        }
        
        .category-sidebar {
            width: var(--sidebar-width);
            background: #2c3e50;
            overflow-y: auto;
            flex-shrink: 0;
        }
        
        .category-btn {
            width: 100%;
            height: 90px;
            border: none;
            background: #34495e;
            color: white;
            font-size: 12px;
            font-weight: 500;
            padding: 8px 4px;
            text-align: center;
            border-bottom: 1px solid #2c3e50;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        
        .category-btn i {
            font-size: 24px;
        }

        .category-btn .category-image {
            width: 34px;
            height: 34px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .category-btn.active,
        .category-btn:hover {
            background: #3498db;
        }
        
        .products-area {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }
        
        .product-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
            height: 210px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .product-card:active {
            transform: scale(0.95);
            border-color: #3498db;
        }
        
        .product-card.out-of-stock {
            opacity: 0.5;
            pointer-events: none;
        }

        .product-card .product-image,
        .product-card .product-fallback {
            width: 92px;
            height: 92px;
            border-radius: 14px;
            object-fit: cover;
            margin-bottom: 8px;
            background: #f1f5f9;
        }

        .product-card .product-fallback {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 32px;
        }

        .product-card .product-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            color: white;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: 700;
        }
        
        .product-card .name {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.2;
            max-height: 34px;
            overflow: hidden;
        }
        
        .product-card .price {
            font-size: 18px;
            font-weight: 700;
            color: #27ae60;
        }
        
        .product-card .stock {
            font-size: 11px;
            color: #7f8c8d;
            margin-top: 4px;
        }
        
        .cart-panel {
            width: var(--cart-width);
            background: white;
            border-left: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        
        .cart-header {
            padding: 15px;
            background: #2c3e50;
            color: white;
        }
        
        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #eee;
            gap: 10px;
        }
        
        .cart-item .qty-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cart-item .qty-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: #ecf0f1;
            font-size: 18px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .cart-item .qty-btn:active {
            background: #3498db;
            color: white;
        }
        
        .cart-item .qty {
            font-size: 16px;
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }
        
        .cart-item .info {
            flex: 1;
        }
        
        .cart-item .info .name {
            font-size: 14px;
            font-weight: 500;
        }
        
        .cart-item .info .price {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .cart-item .remove-btn {
            background: none;
            border: none;
            color: #e74c3c;
            font-size: 20px;
            padding: 5px;
        }
        
        .cart-footer {
            padding: 15px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        
        .cart-total {
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .checkout-btn {
            width: 100%;
            padding: 18px;
            font-size: 18px;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            background: #27ae60;
            color: white;
        }
        
        .checkout-btn:active {
            background: #229954;
        }
        
        .checkout-btn:disabled {
            background: #95a5a6;
        }
        
        .search-bar {
            padding: 10px 15px;
            background: white;
            border-bottom: 1px solid #dee2e6;
        }
        
        .search-bar input {
            border-radius: 25px;
            padding: 12px 20px;
            font-size: 16px;
            border: 2px solid #ecf0f1;
        }
        
        .search-bar input:focus {
            border-color: #3498db;
            box-shadow: none;
        }
        
        .empty-cart {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #95a5a6;
        }
        
        .empty-cart i {
            font-size: 64px;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            :root {
                --sidebar-width: 70px;
                --cart-width: 100%;
            }
            
            .category-btn {
                height: 70px;
                font-size: 10px;
            }
            
            .category-btn i {
                font-size: 20px;
            }

            .category-btn .category-image {
                width: 28px;
                height: 28px;
            }
            
            .product-card {
                height: 190px;
                padding: 10px;
            }

            .product-card .product-image,
            .product-card .product-fallback {
                width: 68px;
                height: 68px;
                font-size: 25px;
            }
            
            .product-card .name {
                font-size: 12px;
            }
            
            .product-card .price {
                font-size: 16px;
            }
            
            .cart-panel {
                position: fixed;
                top: 56px;
                right: -100%;
                width: 100%;
                height: calc(100vh - 56px);
                z-index: 1000;
                transition: right 0.3s;
            }
            
            .cart-panel.show {
                right: 0;
            }
            
            .cart-toggle {
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: #27ae60;
                color: white;
                border: none;
                font-size: 24px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                z-index: 999;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .cart-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                background: #e74c3c;
                color: white;
                border-radius: 50%;
                width: 24px;
                height: 24px;
                font-size: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
        
        @media (min-width: 769px) {
            .cart-toggle {
                display: none;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('panel.inicio') }}">
                <i class="bi bi-shop"></i> {{ $business->nombre_negocio }}
            </a>
            <span class="text-white-50 small d-none d-md-inline me-3">
                <i class="bi bi-geo-alt"></i> {{ $sucursalActual?->nombre ?? 'Sucursal' }}
            </span>
            <div class="d-flex align-items-center">
                @if($printer && $printer->tipo_conexion === 'bluetooth')
                    <button id="conectarBluetoothBtn" type="button" class="btn btn-outline-info btn-sm me-2">
                        <i class="bi bi-bluetooth"></i> Conectar impresora
                    </button>
                @endif
                <button type="button" class="btn btn-outline-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#ventasModal">
                    <i class="bi bi-receipt"></i> Ventas
                </button>
                <a href="{{ route('caja.cerrar.form') }}" class="btn btn-outline-warning btn-sm me-2">
                    <i class="bi bi-lock-fill"></i> Cerrar caja
                </a>
                @if(auth()->user()->esAdminDelNegocioActual())
                    <a href="{{ route('ventas.index') }}" class="btn btn-outline-light btn-sm me-2">
                        <i class="bi bi-receipt"></i> Ventas
                    </a>
                    <a href="{{ route('productos.index') }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-gear"></i> Admin
                    </a>
                @endif
            </div>
        </div>
    </nav>

    @yield('content')

    @include('pos.ventas-modal')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
