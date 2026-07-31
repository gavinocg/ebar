# TPV - Sistema de Punto de Venta

Sistema de punto de venta inspirado en Loyverse, desarrollado con Laravel + Blade + Bootstrap, optimizado para tablets y celulares con soporte para impresoras térmicas de 80mm.

## Características

- **Punto de Venta**: Interfaz táctil optimizada para tablets y móviles
- **Gestión de Productos**: Categorías, productos, control de stock
- **Ventas**: Registro de ventas con múltiples métodos de pago
- **Tickets**: Generación e impresión de tickets en impresoras térmicas
- **Reportes**: Dashboard con métricas de ventas e inventario
- **Impresoras**: Soporte para impresoras térmicas Bluetooth, WiFi y LAN

## Requisitos

- PHP >= 8.1
- MySQL 5.7+ o SQLite
- Composer
- Navegador moderno (Chrome, Edge, Firefox)

## Instalación

1. Clonar o descargar el proyecto en `c:\laragon\www\ebar`

2. Instalar dependencias:
```bash
composer install
```

3. Configurar base de datos en `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ebar
DB_USERNAME=root
DB_PASSWORD=
```

4. Ejecutar migraciones:
```bash
php artisan key:generate
php artisan migrate
```

5. Acceder desde el navegador:
```
http://localhost/ebar/public
```

## Configuración de Impresoras Térmicas

### 1. Agregar Impresora

1. Ir a **Admin > Impresoras**
2. Click en **Nueva Impresora**
3. Configurar según el tipo de conexión:

#### Bluetooth
- **Tipo**: Bluetooth
- **Dirección**: MAC de la impresora (ej: 00:11:22:33:44:55)
- **Puerto**: 1 (por defecto)

#### WiFi / LAN
- **Tipo**: WiFi o LAN
- **Dirección**: IP de la impresora (ej: 192.168.1.100)
- **Puerto**: 9100 (estándar para impresoras térmicas)

### 2. Protocolos de Impresión

El sistema soporta tres métodos de conexión:

#### Bluetooth (Web Bluetooth API)
- Requiere navegador Chrome/Edge compatible
- La impresora debe soportar el perfil BLE (Bluetooth Low Energy)
- Emparejamiento manual desde el navegador

#### WiFi / LAN (TCP/IP)
- Conexión directa por red local
- Requiere que la impresora tenga IP fija
- Puerto estándar: 9100 (ESC/POS)

#### Fallback (Impresión del Navegador)
- Si la conexión directa falla, se abre ventana de impresión del navegador
- Compatible con cualquier impresora configurada en el sistema

### 3. Formato de Ticket

El sistema genera tickets en formato ESC/POS optimizados para papel de 80mm:
- Ancho: 42 caracteres
- Codificación: ASCII + comandos ESC/POS
- Incluye: logo, productos, totales, código de barras (opcional)

## Estructura del Proyecto

```
ebar/
├── app/
│   ├── Http/Controllers/
│   │   ├── PosController.php       # Punto de venta
│   │   ├── ProductController.php   # Gestión de productos
│   │   ├── CategoryController.php  # Categorías
│   │   ├── SaleController.php      # Historial de ventas
│   │   ├── PrinterController.php   # Gestión de impresoras
│   │   └── DashboardController.php # Reportes
│   ├── Models/
│   │   ├── Product.php
│   │   ├── Category.php
│   │   ├── Sale.php
│   │   ├── SaleItem.php
│   │   └── Printer.php
│   └── Services/
│       └── ThermalPrinterService.php # Generación de tickets ESC/POS
├── resources/views/
│   ├── layouts/
│   │   ├── app.blade.php          # Layout principal
│   │   └── pos.blade.php          # Layout del POS
│   ├── pos/
│   │   └── index.blade.php        # Interfaz del punto de venta
│   ├── products/
│   ├── categories/
│   ├── sales/
│   ├── printers/
│   └── dashboard/
└── routes/
    └── web.php
```

## Uso del Punto de Venta

### Interfaz Táctil

1. **Seleccionar Categoría**: Click en la barra lateral izquierda
2. **Agregar Productos**: Click en las tarjetas de productos
3. **Ajustar Cantidad**: Botones +/- en el carrito
4. **Buscar**: Barra de búsqueda superior (soporta códigos de barras)
5. **Cobrar**: Botón "COBRAR" > Seleccionar método de pago > "Cobrar e Imprimir"

### Métodos de Pago

- Efectivo
- Tarjeta
- Transferencia

### Impresión de Tickets

Al cobrar, el sistema:
1. Registra la venta en la base de datos
2. Descuenta el stock automáticamente
3. Genera el ticket en formato ESC/POS
4. Envía a la impresora configurada
5. Si falla, abre ventana de impresión del navegador

## Despliegue en Hosting Compartido (cPanel)

### 1. Subir Archivos

Subir todos los archivos excepto:
- `vendor/`
- `node_modules/`
- `.env`

### 2. Configurar en cPanel

1. Crear base de datos MySQL desde cPanel
2. Subir archivos al directorio `public_html` o subdominio
3. Configurar `.env` con las credenciales de la base de datos
4. Ejecutar migraciones desde terminal SSH o phpMyAdmin

### 3. Configurar Document Root

En cPanel, configurar el document root para que apunte a `/public`:
```
public_html/ebar/public
```

### 4. Permisos

Asegurar permisos de escritura en:
```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

## Comandos Útiles

```bash
# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Crear usuario admin (si se implementa autenticación)
php artisan make:auth
```

## Soporte para Impresoras

### Impresoras Compatibles

Cualquier impresora térmica que soporte:
- **Protocolo**: ESC/POS
- **Conexión**: Bluetooth BLE, WiFi, o Ethernet
- **Papel**: 80mm (recomendado) o 58mm

### Modelos Recomendados

- Epson TM-T88VI
- Star TSP143III
- Bixolon SRP-350III
- Xprinter XP-80 (económica)

### Troubleshooting

**Impresora no responde:**
1. Verificar que la impresora esté encendida y conectada
2. Confirmar IP/MAC correcta en configuración
3. Probar conexión con app de prueba del fabricante
4. Verificar firewall no bloquee el puerto 9100

**Error de Bluetooth:**
1. Asegurar que el navegador soporte Web Bluetooth (Chrome/Edge)
2. Emparejar la impresora desde configuración del sistema
3. Verificar que la impresora soporte perfil BLE

**Ticket en blanco:**
1. Verificar que el papel esté cargado correctamente
2. Limpiar cabezal de impresión
3. Ajustar densidad en configuración de la impresora

## Licencia

MIT

## Créditos

Inspirado en [Loyverse POS](https://loyverse.com)
