@php
    $business = \App\Models\ConfiguracionNegocio::obtenerConfiguracion();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TPV') - {{ $business->nombre_negocio }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('panel.inicio') }}">
                <i class="bi bi-shop"></i> {{ $business->nombre_negocio }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('punto_venta.inicio') }}">
                            <i class="bi bi-cart"></i> Punto de Venta
                        </a>
                    </li>
                    @if (auth()->user()?->esAdminDelNegocioActual())
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('productos.index') }}">
                            <i class="bi bi-box"></i> Productos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('categorias.index') }}">
                            <i class="bi bi-tags"></i> Categorías
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('ventas.index') }}">
                            <i class="bi bi-receipt"></i> Ventas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('impresoras.index') }}">
                            <i class="bi bi-printer"></i> Impresoras
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('configuracion.negocio') }}">
                            <i class="bi bi-gear"></i> Configuración
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('sucursales.index') }}">
                            <i class="bi bi-diagram-3"></i> Sucursales
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('cajeros.index') }}">
                            <i class="bi bi-people"></i> Cajeros
                        </a>
                    </li>
                    @endif
                    @if (auth()->check() && auth()->user()->rol !== 'super_admin')
                        <li class="nav-item">
                            <form method="POST" action="{{ route('negocio.cambiar') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="nav-link btn btn-link">
                                    <i class="bi bi-arrow-left-right"></i> Cambiar de bar
                                </button>
                            </form>
                        </li>
                    @endif
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-graph-up"></i> Reportes
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('panel.inicio') }}">Panel</a></li>
                            <li><a class="dropdown-item" href="{{ route('reportes.ventas') }}">Ventas</a></li>
                            <li><a class="dropdown-item" href="{{ route('reportes.inventario') }}">Inventario</a></li>
                            <li><a class="dropdown-item" href="{{ route('reportes.cajeros') }}">Por cajero</a></li>
                            <li><a class="dropdown-item" href="{{ route('caja.reporte') }}">Arqueos de caja</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-3">
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

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
