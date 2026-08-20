@php
    $business = \App\Models\ConfiguracionNegocio::obtenerConfiguracion();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel') - {{ $business->nombre_negocio }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed: 0px;
        }
        
        body {
            overflow-x: hidden;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            transition: all 0.3s;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar.collapsed {
            left: calc(-1 * var(--sidebar-width));
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h4 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .sidebar-menu li {
            margin: 5px 15px;
        }
        
        .sidebar-menu a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-menu a i {
            font-size: 20px;
            margin-right: 12px;
            width: 24px;
            text-align: center;
        }
        
        .sidebar-menu .menu-label {
            font-size: 11px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.4);
            padding: 15px 15px 5px;
            letter-spacing: 1px;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            transition: all 0.3s;
            min-height: 100vh;
            background: #f8f9fa;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .top-navbar {
            background: white;
            padding: 15px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .toggle-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: #2c3e50;
            cursor: pointer;
            padding: 5px;
        }
        
        .content-wrapper {
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                left: calc(-1 * var(--sidebar-width));
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
                display: none;
            }
            
            .overlay.show {
                display: block;
            }
        }
        
        @media (min-width: 769px) {
            .sidebar {
                left: 0;
            }
            
            .sidebar.collapsed {
                left: calc(-1 * var(--sidebar-width));
            }
            
            .main-content {
                margin-left: var(--sidebar-width);
            }
            
            .main-content.expanded {
                margin-left: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="overlay" id="overlay"></div>
    
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-shop"></i> {{ auth()->user()->rol === 'super_admin' ? 'e-Bar' : $business->nombre_negocio }}</h4>
        </div>
        
        <ul class="sidebar-menu">
            @if(auth()->user()->rol === 'super_admin')
            <li class="menu-label">Plataforma</li>
            <li>
                <a href="{{ route('plataforma.inicio') }}" class="{{ request()->routeIs('plataforma.inicio') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('plataforma.negocios.index') }}" class="{{ request()->routeIs('plataforma.negocios.*') ? 'active' : '' }}">
                    <i class="bi bi-shop"></i>
                    <span>Bares</span>
                </a>
            </li>
            @else
            <li class="menu-label">Principal</li>
            <li>
                <a href="{{ route('panel.inicio') }}" class="{{ request()->routeIs('panel.*') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            @if(auth()->user()->tienePermiso('pos.ver'))
            <li>
                <a href="{{ route('punto_venta.inicio') }}" class="{{ request()->routeIs('punto_venta.*') ? 'active' : '' }}">
                    <i class="bi bi-cart"></i>
                    <span>Punto de Venta</span>
                </a>
            </li>
            @endif

            @if(auth()->user()->tienePermiso('producto.ver') || auth()->user()->tienePermiso('categoria.ver') || auth()->user()->tienePermiso('inventario.ver') || auth()->user()->tienePermiso('inventario.conteos'))
            <li class="menu-label">Inventario</li>
            @endif
            @if(auth()->user()->tienePermiso('producto.ver'))
            <li>
                <a href="{{ route('productos.index') }}" class="{{ request()->routeIs('productos.*') ? 'active' : '' }}">
                    <i class="bi bi-box"></i>
                    <span>Productos</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('categoria.ver'))
            <li>
                <a href="{{ route('categorias.index') }}" class="{{ request()->routeIs('categorias.*') ? 'active' : '' }}">
                    <i class="bi bi-tags"></i>
                    <span>Categorías</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('inventario.ver'))
            <li>
                <a href="{{ route('inventario.historial') }}" class="{{ request()->routeIs('inventario.*') ? 'active' : '' }}">
                    <i class="bi bi-clock-history"></i>
                    <span>Historial</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('inventario.conteos'))
            <li>
                <a href="{{ route('conteos.index') }}" class="{{ request()->routeIs('conteos.*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-check"></i>
                    <span>Conteos</span>
                </a>
            </li>
            @endif

            @if(auth()->user()->tienePermiso('proveedor.ver') || auth()->user()->tienePermiso('orden.ver'))
            <li class="menu-label">Compras</li>
            @endif
            @if(auth()->user()->tienePermiso('proveedor.ver'))
            <li>
                <a href="{{ route('proveedores.index') }}" class="{{ request()->routeIs('proveedores.*') ? 'active' : '' }}">
                    <i class="bi bi-truck"></i>
                    <span>Proveedores</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('orden.ver'))
            <li>
                <a href="{{ route('ordenes.index') }}" class="{{ request()->routeIs('ordenes.*') ? 'active' : '' }}">
                    <i class="bi bi-cart-plus"></i>
                    <span>Órdenes de compra</span>
                </a>
            </li>
            @endif

            @if(auth()->user()->tienePermiso('venta.ver'))
            <li class="menu-label">Ventas</li>
            <li>
                <a href="{{ route('ventas.index') }}" class="{{ request()->routeIs('ventas.*') ? 'active' : '' }}">
                    <i class="bi bi-receipt"></i>
                    <span>Historial</span>
                </a>
            </li>
            @endif

            @if(auth()->user()->tienePermiso('reporte.ventas') || auth()->user()->tienePermiso('reporte.productos') || auth()->user()->tienePermiso('reporte.categorias') || auth()->user()->tienePermiso('reporte.metodos_pago') || auth()->user()->tienePermiso('reporte.tendencias') || auth()->user()->tienePermiso('reporte.sucursal') || auth()->user()->tienePermiso('reporte.inventario') || auth()->user()->tienePermiso('reporte.cajeros'))
            <li class="menu-label">Reportes</li>
            @endif
            @if(auth()->user()->tienePermiso('reporte.ventas'))
            <li>
                <a href="{{ route('reportes.ventas') }}" class="{{ request()->routeIs('reportes.ventas') ? 'active' : '' }}">
                    <i class="bi bi-graph-up"></i>
                    <span>Ventas</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('reporte.productos'))
            <li>
                <a href="{{ route('reportes.productos') }}" class="{{ request()->routeIs('reportes.productos') ? 'active' : '' }}">
                    <i class="bi bi-trophy"></i>
                    <span>Productos</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('reporte.categorias'))
            <li>
                <a href="{{ route('reportes.categorias') }}" class="{{ request()->routeIs('reportes.categorias') ? 'active' : '' }}">
                    <i class="bi bi-tags"></i>
                    <span>Categorias</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('reporte.metodos_pago'))
            <li>
                <a href="{{ route('reportes.metodos_pago') }}" class="{{ request()->routeIs('reportes.metodos_pago') ? 'active' : '' }}">
                    <i class="bi bi-credit-card"></i>
                    <span>Metodos de Pago</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('reporte.tendencias'))
            <li>
                <a href="{{ route('reportes.tendencias') }}" class="{{ request()->routeIs('reportes.tendencias') ? 'active' : '' }}">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Tendencias</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('reporte.sucursal'))
            <li>
                <a href="{{ route('reportes.sucursal') }}" class="{{ request()->routeIs('reportes.sucursal') ? 'active' : '' }}">
                    <i class="bi bi-building"></i>
                    <span>Sucursal/Cajero</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('reporte.inventario'))
            <li>
                <a href="{{ route('reportes.inventario') }}" class="{{ request()->routeIs('reportes.inventario') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart"></i>
                    <span>Inventario</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('reporte.cajeros'))
            <li>
                <a href="{{ route('reportes.cajeros') }}" class="{{ request()->routeIs('reportes.cajeros') ? 'active' : '' }}">
                    <i class="bi bi-people"></i>
                    <span>Cajeros</span>
                </a>
            </li>
            @endif

            @if(auth()->user()->tienePermiso('cuadre.aprobar') || auth()->user()->tienePermiso('impresora.ver') || auth()->user()->tienePermiso('sucursal.ver') || auth()->user()->tienePermiso('usuario.cajeros') || auth()->user()->tienePermiso('usuario.admin_bar') || auth()->user()->tienePermiso('caja.reporte') || auth()->user()->tienePermiso('auditoria.ver') || auth()->user()->tienePermiso('rol.gestionar') || auth()->user()->tienePermiso('reembolso.ver') || auth()->user()->tienePermiso('configuracion.negocio'))
            <li class="menu-label">Configuración</li>
            @endif
            @if(auth()->user()->tienePermiso('cuadre.aprobar'))
            <li>
                <a href="{{ route('cuadres.pendientes') }}" class="{{ request()->routeIs('cuadres.*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-check"></i>
                    <span>Cuadres pendientes</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('impresora.ver'))
            <li>
                <a href="{{ route('impresoras.index') }}" class="{{ request()->routeIs('impresoras.*') ? 'active' : '' }}">
                    <i class="bi bi-printer"></i>
                    <span>Impresoras</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('sucursal.ver'))
            <li>
                <a href="{{ route('sucursales.index') }}" class="{{ request()->routeIs('sucursales.*') ? 'active' : '' }}">
                    <i class="bi bi-buildings"></i>
                    <span>Sucursales</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('usuario.cajeros'))
            <li>
                <a href="{{ route('cajeros.index') }}" class="{{ request()->routeIs('cajeros.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i>
                    <span>Cajeros</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('usuario.admin_bar'))
            <li>
                <a href="{{ route('admin-bar.index') }}" class="{{ request()->routeIs('admin-bar.*') ? 'active' : '' }}">
                    <i class="bi bi-person-gear"></i>
                    <span>Admins de bar</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('caja.reporte'))
            <li>
                <a href="{{ route('caja.reporte') }}" class="{{ request()->routeIs('caja.reporte', 'caja.turno-detalle') ? 'active' : '' }}">
                    <i class="bi bi-cash-stack"></i>
                    <span>Arqueos</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('auditoria.ver'))
            <li>
                <a href="{{ route('auditorias.index') }}" class="{{ request()->routeIs('auditorias.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-check"></i>
                    <span>Auditoría</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('rol.gestionar'))
            <li>
                <a href="{{ route('roles.index') }}" class="{{ request()->routeIs('roles.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock"></i>
                    <span>Roles</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('reembolso.ver'))
            <li>
                <a href="{{ route('reembolsos.index') }}" class="{{ request()->routeIs('reembolsos.*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <span>Reembolsos</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->tienePermiso('configuracion.negocio'))
            <li>
                <a href="{{ route('configuracion.negocio') }}" class="{{ request()->routeIs('configuracion.*') ? 'active' : '' }}">
                    <i class="bi bi-gear"></i>
                    <span>Configuración</span>
                </a>
            </li>
            @endif
            @endif
        </ul>
    </aside>
    
    <div class="main-content" id="mainContent">
        <div class="top-navbar">
            <button class="toggle-btn" id="toggleSidebar">
                <i class="bi bi-list"></i>
            </button>
            <div class="d-flex align-items-center gap-2">
                <span class="fw-semibold">{{ auth()->user()->rol === 'super_admin' ? 'Plataforma' : 'Administración' }}</span>
                <span class="text-muted">@yield('breadcrumb')</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="small text-muted d-none d-md-inline">{{ auth()->user()->nombre ?? '' }}</span>
                <form method="POST" action="{{ route('cerrar_sesion') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </button>
                </form>
            </div>
        </div>
        
        <div class="content-wrapper">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('no_eliminable'))
                <div class="modal fade" id="modalNoEliminable" tabindex="-1" aria-labelledby="modalNoEliminableLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalNoEliminableLabel">No se puede eliminar</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-2">
                                    Este {{ session('no_eliminable.entidad') }} no se puede eliminar porque tiene registros dependientes:
                                </p>
                                <ul class="mb-3">
                                    @foreach(session('no_eliminable.dependencias') as $dependencia)
                                        <li>{{ $dependencia }}</li>
                                    @endforeach
                                </ul>
                                <p class="mb-0">
                                    ¿Deseas desactivarlo? El registro pasará a estado INACTIVO y se mantendrá visible en el listado.
                                </p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <form method="POST" action="{{ session('no_eliminable.url') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-pause-circle"></i> Sí, desactivar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const modal = document.getElementById('modalNoEliminable');
                        if (modal && window.bootstrap) {
                            new bootstrap.Modal(modal).show();
                        }
                    });
                </script>
            @endif
            
            @yield('content')
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const toggleBtn = document.getElementById('toggleSidebar');
        const overlay = document.getElementById('overlay');
        
        const isMobile = () => window.innerWidth <= 768;
        
        toggleBtn.addEventListener('click', () => {
            if (isMobile()) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        });
        
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
        
        window.addEventListener('resize', () => {
            if (!isMobile()) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
