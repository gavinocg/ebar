# Plan De Desarrollo TPV Ecuador

## Objetivo

Convertir e-Bar en una plataforma SaaS multi-tenant para administrar bares escolares, segura y orientada a dispositivos móviles, tomando como referencia funcional las capacidades públicas de Loyverse sin copiar su identidad visual ni sus recursos propietarios.

## Reglas De Trabajo

- Cada funcionalidad se implementará como una unidad atómica.
- Antes de editar se revisarán modelo, rutas, vistas, permisos y dependencias.
- Cada unidad debe incluir migración, lógica, interfaz y pruebas cuando corresponda.
- Las ventas, existencias, cajas y permisos deben validarse en el servidor.
- No se registrarán clientes específicos; las ventas usarán consumidor genérico.
- Para ventas a crédito se usará una agenda mínima de clientes con nombre y descripción; no será un CRM.
- El `super_admin` administra la plataforma y vende membresías; cada bar es un tenant independiente.
- No se ejecutará `migrate:fresh` en producción.
- Las migraciones productivas usarán `php artisan migrate --force`.
- Después de cada unidad se actualizará este archivo.
- Un cambio llega a producción únicamente mediante merge hacia `prod`.
- La limpieza de pruebas se ejecutará con `php artisan clean-transactional`.
- `clean-transactional` no elimina configuración, usuarios, impresoras, cajas ni migraciones.

## Estados

- `COMPLETADO`: implementado y validado.
- `EN_PROGRESO`: se está implementando.
- `PENDIENTE`: planificado, todavía no iniciado.
- `BLOQUEADO`: requiere una decisión, servicio externo o dato pendiente.

## Estado Actual

### Modelo De Negocio e-Bar

- `super_admin`: administra la plataforma, crea bares y vende membresías.
- `admin_bar`: administra un bar, caja, catálogo, reportes y cajeros; también puede operar como cajero.
- `cajero`: usuario limitado al POS y a sus turnos de caja.
- `negocio`: representa un bar escolar y funciona como tenant aislado.
- `membresia`: relaciona un bar con un plan, estado, fechas y límites de uso.
- No habrá CRM general; los datos de crédito serán una agenda mínima por venta.

### Base Y Seguridad

- [x] `COMPLETADO` Autenticación y cierre de sesión.
- [x] `COMPLETADO` Protección de rutas con middleware `auth`.
- [x] `COMPLETADO` Validación estricta del checkout.
- [x] `COMPLETADO` Bloqueo transaccional de existencias.
- [x] `COMPLETADO` Idempotencia para evitar ventas duplicadas.
- [x] `COMPLETADO` Folios seguros de comprobantes.
- [x] `COMPLETADO` Snapshot de impuestos por venta.
- [x] `COMPLETADO` Registro de movimientos de inventario por venta.
- [x] `COMPLETADO` Pruebas de checkout, páginas principales y caja.

### Localización Y Experiencia Visual

- [x] `COMPLETADO` Tablas y columnas de dominio en español.
- [x] `COMPLETADO` Modelos, controladores y servicios propios en español.
- [x] `COMPLETADO` Configuración regional de Ecuador.
- [x] `COMPLETADO` Diseño POS responsive para móvil y tableta.
- [x] `COMPLETADO` Backoffice con navegación lateral consistente con el POS.
- [x] `COMPLETADO` Imágenes, colores, iconos y distintivos para productos y categorías.
- [x] `COMPLETADO` Selector visual de iconos gastronómicos para categorías.
- [x] `COMPLETADO` Comprobante térmico compacto para papel de 58 mm.
- [x] `COMPLETADO` Columnas izquierda/derecha para artículos e importes en tickets térmicos.
- [x] `COMPLETADO` Conexión Bluetooth previa para Android Chrome.
- [x] `COMPLETADO` Envío térmico directo sin vista previa cuando existe impresora conectada.
- [x] `COMPLETADO` Transporte BLE en paquetes compactos para impresoras portátiles.

### Operación De Caja

- [x] `COMPLETADO` Caja activa por defecto.
- [x] `COMPLETADO` Apertura de turno con fondo inicial.
- [x] `COMPLETADO` Cierre de turno con efectivo contado.
- [x] `COMPLETADO` Cálculo de efectivo esperado y diferencia.
- [x] `COMPLETADO` Asociación de venta con usuario y turno.
- [x] `COMPLETADO` Entradas y retiros manuales de efectivo.
- [x] `COMPLETADO` Reapertura autorizada de turnos cerrados.
- [x] `COMPLETADO` Reporte de arqueos y diferencias por cajero.

### Despliegue

- [x] `COMPLETADO` Ramas `dev` y `prod` publicadas.
- [x] `COMPLETADO` Aplicación desplegada en `192.168.100.101`.
- [x] `COMPLETADO` Servicio Laravel persistente en `127.0.0.1:8001`.
- [x] `COMPLETADO` Base MySQL creada y migrada en el servidor.
- [x] `COMPLETADO` Sincronizador `ebar-sync.timer` para revisar `origin/prod`.
- [x] `COMPLETADO` Cloudflare Tunnel configurado hacia `http://localhost:8001`.
- [ ] `PENDIENTE` Rotar el token de Cloudflare compartido durante la configuración.

## Programación De Fases

| Fase | Alcance | Dependencia | Estado |
|---|---|---|---|
| 1 | Plataforma SaaS: super administrador, planes, bares y membresías | Fundación multi-tenant | COMPLETADO |
| 2 | Cajeros, PIN, roles y permisos por bar | Fase 1 | COMPLETADO |
| 3 | Caja, movimientos, cierres y arqueos avanzados | Fases 1 y 2 | COMPLETADO |
| 4 | Inventario avanzado, compras y ajustes | Fase 1 | COMPLETADO |
| 5 | Crédito, cuentas por cobrar, descuentos y devoluciones | Fases 2 y 3 | EN_PROGRESO |
| 6 | Reportes, exportación y auditoría | Fases 2 a 5 | PENDIENTE |
| 7 | Operación móvil, PWA y modo offline | Fases 1 a 6 | PENDIENTE |
| 8 | Restaurante, cocina e integraciones | Según necesidad del negocio | PENDIENTE |

## Criterios De Cierre Por Fase

- Migraciones aplicadas sin `migrate:fresh` en producción.
- Rutas protegidas y autorización validada en servidor.
- Interfaz responsive validada en escritorio, tableta y Android.
- Pruebas automáticas para el flujo principal y errores críticos.
- Actualización de esta sección y del registro de implementaciones.
- Commit en `dev`, pruebas aprobadas, merge a `prod` y verificación del servidor.

## Fase 1: Multi-Tenant `COMPLETADO`

### 1.1 Negocios

- [x] `COMPLETADO` Crear tabla `negocios`.
- [x] `COMPLETADO` Crear modelo `Negocio`.
- [x] `COMPLETADO` Crear CRUD administrativo de negocios para `super_admin`.
- [x] `COMPLETADO` Definir identificador único del negocio.
- [x] `COMPLETADO` Definir zona horaria, moneda y configuración por negocio.
- [x] `COMPLETADO` Asociar administrador principal del bar.
- [x] `COMPLETADO` Definir estados: prueba, activo, suspendido, vencido y cancelado.

### 1.2 Sucursales

- [x] `COMPLETADO` Crear tabla `sucursales` con `negocio_id`.
- [x] `COMPLETADO` Crear modelo `Sucursal`.
- [x] `COMPLETADO` Crear CRUD de sucursales.
- [x] `COMPLETADO` Asociar cajas, impresoras, productos y ventas a sucursales.

### 1.3 Membresías

- [x] `COMPLETADO` Crear tabla `membresias_negocio`.
- [x] `COMPLETADO` Permitir que un usuario pertenezca a uno o varios negocios.
- [x] `COMPLETADO` Crear selector de negocio y sucursal después del login.
- [x] `COMPLETADO` Crear `ContextoNegocio` para negocio, sucursal y usuario actuales.

### 1.4 Aislamiento

- [x] `COMPLETADO` Crear middleware de tenant.
- [x] `COMPLETADO` Aplicar scopes obligatorios por `negocio_id`.
- [x] `COMPLETADO` Validar relaciones dentro del negocio actual.
- [x] `COMPLETADO` Crear índices compuestos de aislamiento.
- [x] `COMPLETADO` Crear pruebas de lectura cruzada entre negocios.

### 1.5 Membresías Y Super Administrador

- [x] `COMPLETADO` Crear planes de membresía.
- [x] `COMPLETADO` Crear fechas de inicio, vencimiento y renovación.
- [x] `COMPLETADO` Crear límites por plan para cajeros, cajas, sucursales y almacenamiento.
- [x] `COMPLETADO` Crear rol global `super_admin` fuera del tenant.
- [x] `COMPLETADO` Crear panel global de bares.
- [x] `COMPLETADO` Crear alta de bar y administrador inicial.
- [x] `COMPLETADO` Activar, suspender y reactivar bares.
- [x] `COMPLETADO` Bloquear bares vencidos o suspendidos.

### Unidades De Implementación De La Fase 1

- [x] `COMPLETADO` 1A. Crear rol global `super_admin` y separar acceso de plataforma.
- [x] `COMPLETADO` 1B. Crear planes de membresía y límites por plan.
- [x] `COMPLETADO` 1C. Crear CRUD global de bares para `super_admin`.
- [x] `COMPLETADO` 1D. Crear alta de administrador inicial de cada bar.
- [x] `COMPLETADO` 1E. Crear alta, renovación, suspensión y vencimiento de membresías.
- [x] `COMPLETADO` 1F. Crear selector de negocio y sucursal.
- [x] `COMPLETADO` 1G. Aplicar pruebas de aislamiento y cierre de fase.

## Fase 2: Cajeros Y Permisos

- [x] `COMPLETADO` Definir roles por bar: `admin_bar` y `cajero`.
- [x] `COMPLETADO` Permitir que `admin_bar` opere también como cajero.
- [x] `COMPLETADO` Permitir registrar una cantidad de cajeros limitada por la membresía.
- [x] `COMPLETADO` Desactivar cajeros sin borrar su historial.
- [x] `COMPLETADO` Implementar PIN numérico de 4 dígitos.
- [x] `COMPLETADO` Crear acceso de cajero exclusivo para POS.
- [x] `COMPLETADO` Impedir que un cajero acceda al backoffice.
- [x] `COMPLETADO` Permitir a `admin_bar` administrar sus cajeros.
- [x] `COMPLETADO` Crear permisos por módulo y acción.
- [x] `COMPLETADO` Aplicar policies a productos, ventas, cajas, reportes y configuración.
- [x] `COMPLETADO` Implementar PIN de acceso rápido al POS.
- [x] `COMPLETADO` Registrar usuario responsable en ventas y movimientos.
- [x] `COMPLETADO` Crear auditoría de descuentos, devoluciones, ajustes y reaperturas.
- [x] `COMPLETADO` Crear reportes de ventas por cajero.

## Fase 3: Efectivo Y Arqueos

- [x] `COMPLETADO` Crear `movimientos_efectivo`.
- [x] `COMPLETADO` Registrar ventas en efectivo.
- [x] `COMPLETADO` Registrar retiros, gastos y entradas.
- [x] `COMPLETADO` Exigir motivo en retiros y ajustes.
- [x] `COMPLETADO` Mejorar cálculo de efectivo esperado.
- [x] `COMPLETADO` Crear reporte de turno.
- [x] `COMPLETADO` Crear reporte de diferencias por usuario, caja y sucursal.

## Fase 4: Inventario Avanzado

- [x] `COMPLETADO` Permitir productos con existencias controladas o disponibilidad indefinida.
- [x] `COMPLETADO` Crear ajustes manuales auditados.
- [x] `COMPLETADO` Crear pantalla de historial de inventario.
- [x] `COMPLETADO` Agregar motivo, usuario y referencia a cada ajuste.
- [x] `COMPLETADO` Crear niveles mínimos configurables por producto.
- [x] `COMPLETADO` Crear alertas de existencias bajas.
- [x] `COMPLETADO` Separar catálogo de existencias por sucursal.
- [x] `COMPLETADO` Crear proveedores y órdenes de compra.
- [x] `COMPLETADO` Crear recepción de mercancía.
- [x] `COMPLETADO` Crear conteos físicos.
- [x] `COMPLETADO` Crear importación y exportación CSV.
- [x] `COMPLETADO` Crear impresión de etiquetas.

## Fase 5: Ventas Avanzadas

- [x] `COMPLETADO` Crear descuentos por producto.
- [x] `COMPLETADO` Crear descuentos por comprobante.
- [x] `COMPLETADO` Congelar descuentos en el detalle de venta.
- [x] `COMPLETADO` Crear reembolsos parciales y totales.
- [x] `COMPLETADO` Revertir existencias mediante movimientos compensatorios.
- [x] `COMPLETADO` Exigir autorización para anulaciones y devoluciones.
- [ ] Crear tickets abiertos.
- [ ] Crear pagos divididos.
- [ ] Crear variantes de productos.
- [ ] Crear modificadores y extras.

## Fase 6: Analítica Y Exportación

- [ ] Corregir y ampliar reporte de impuestos.
- [ ] Crear ranking de productos más vendidos.
- [ ] Crear ventas por categoría.
- [ ] Crear tendencias comparativas por periodo.
- [ ] Crear reportes por método de pago.
- [ ] Crear reportes por cajero, caja y sucursal.
- [ ] Exportar ventas a CSV.
- [ ] Exportar reportes a XLSX/PDF.

## Fase 7: Restaurante Opcional

- [ ] Crear tipos de pedido: local, llevar y domicilio.
- [ ] Crear tickets abiertos por mesa.
- [ ] Crear estaciones de cocina.
- [ ] Crear impresoras de cocina.
- [ ] Crear pantalla KDS.
- [ ] Crear notas de preparación.
- [ ] Crear estados de preparación.

## Fase 8: Operación Móvil

- [ ] Eliminar `user-scalable=no` para mejorar accesibilidad.
- [ ] Implementar carga progresiva del catálogo.
- [ ] Implementar búsqueda AJAX para catálogos grandes.
- [ ] Crear modo oscuro configurable.
- [ ] Crear pantalla para clientes.
- [ ] Crear PWA instalable.
- [ ] Crear cola offline con idempotencia.
- [ ] Sincronizar ventas al recuperar conexión.

## Fase 9: Integraciones

- [ ] Crear API versionada.
- [ ] Crear tokens de integración por negocio.
- [ ] Crear webhooks de ventas e inventario.
- [ ] Integrar proveedores de pagos compatibles con Ecuador.
- [ ] Integrar contabilidad o facturación electrónica si se requiere.

## Próximo Paso

La siguiente unidad es **tickets abiertos** (Fase 5). Después seguirán pagos divididos, variantes de productos y modificadores/extras.

## Flujo Operativo Definido

### Acceso Del Cajero

- El cajero inicia sesión con cédula y PIN numérico de 4 dígitos.
- El PIN se almacena cifrado/hasheado; nunca se guarda en texto plano.
- La cédula identifica al usuario y debe ser única dentro del negocio.
- Los roles iniciales serán `cajero` y `admin_bar`.
- `admin_bar` podrá administrar caja, cajeros, reportes y operaciones sensibles.

### Inicio Del Turno

- Después del login, si no hay turno abierto, el sistema lleva a apertura de caja.
- El cajero selecciona la caja y registra el fondo inicial.
- No se permite vender sin turno abierto.
- La venta queda asociada a usuario, caja, turno y sucursal cuando exista multi-tenant.

### Formas De Pago

- `efectivo`: registra el dinero recibido y alimenta el cuadre de caja.
- `credito`: no aumenta efectivo; crea una cuenta por cobrar con nombre y descripción de la operación.
- `transferencia`: registra entidad financiera y número de comprobante; no aumenta efectivo.
- Los datos del crédito serán propios de la venta y no crearán una base permanente de clientes.

### Cierre Y Cuadre

- El cajero o `admin_bar` solicita el cierre del turno.
- El sistema calcula efectivo esperado usando fondo, ventas en efectivo, entradas, retiros y gastos.
- El cajero registra efectivo contado.
- Se calcula la diferencia.
- El cierre queda inmutable salvo reapertura autorizada por `admin_bar`.
- Se registra auditoría de cierres, diferencias y reaperturas.

## Fase 10: Flujo De Cajero Y Cobros

- [ ] Cambiar login a cédula y PIN de 4 dígitos.
- [ ] Validar y hashear el PIN.
- [ ] Crear gestión de roles `cajero` y `admin_bar`.
- [ ] Llevar al cajero a apertura de caja después del login.
- [ ] Bloquear el POS sin turno abierto.
- [ ] Mantener efectivo como movimiento de caja.
- [ ] Agregar crédito y cuenta por cobrar por venta.
- [ ] Agregar entidad y comprobante para transferencias.
- [ ] Separar pagos que afectan caja de pagos que no afectan caja.
- [ ] Implementar cierre y cuadre por cajero.
- [ ] Implementar reapertura autorizada por `admin_bar`.
- [ ] Crear pruebas de acceso, caja, pagos y aislamiento.

## Fase 11: Agenda Mínima Para Crédito

- [x] `COMPLETADO` Crear tabla `clientes` con nombre, descripción y estado activo.
- [x] `COMPLETADO` Crear búsqueda incremental por caracteres digitados.
- [x] `COMPLETADO` Mostrar opciones con nombre, descripción y botón `Seleccionar`.
- [x] `COMPLETADO` Exigir selección de cliente para una venta a crédito.
- [x] `COMPLETADO` Guardar `cliente_id` y snapshot de nombre/descripción en la venta.
- [ ] Mantener esta agenda separada de fidelización, puntos y CRM.

## Registro De Implementaciones

### 2026-08-01

- Se creó este plan.
- Se documentó el estado funcional actual.
- Se documentó la estrategia multi-tenant.
- Se documentó el flujo de caja y cajeros.
- Se excluyó deliberadamente el módulo de clientes específicos.

### 2026-08-01 - Completado

- Se implementó la unidad de movimientos manuales de efectivo.
- Se agregaron entradas, retiros y gastos con motivo obligatorio.
- Las ventas en efectivo generan movimientos automáticos.
- El cierre calcula el efectivo esperado a partir del libro de movimientos.
- Se agregaron pruebas de caja, movimientos y checkout.

### 2026-08-01 - Siguiente Unidad

- Reapertura autorizada de turnos cerrados.
- Reporte de arqueos y diferencias por cajero.

### 2026-08-01 - Ticket Térmico 58 mm

- Se implementó formato compacto para rollos térmicos de 58 mm.
- Se agregó encabezado centrado con nombre del negocio y fecha.
- Se agregaron columnas alineadas para descripción e importe.
- Se redujo el espaciado y se limitaron separadores a 32 columnas.
- Se agregó vista HTML compacta para impresión alternativa.
- Se corrigieron las claves de conexión Bluetooth y la ruta de prueba de impresoras.
- Se agregaron pruebas de formato del comprobante.

### 2026-08-01 - Bluetooth Android

- Se agregó conexión Bluetooth iniciada por una acción directa del usuario.
- El POS ya permite conectar la impresora antes de cobrar.
- La prueba de impresora conecta antes de solicitar el ticket.
- Se dejó pendiente validar físicamente la WDT-492 y confirmar si utiliza BLE o Bluetooth clásico.

### 2026-08-01 - WDT-492 Validada

- Se confirmó que la WDT-492 expone Bluetooth BLE.
- Se confirmó el servicio de impresión `0x18F0`.
- Se confirmó impresión correcta desde Chrome Android.
- La conexión debe desconectarse previamente de nRF Connect antes de usar el TPV.
- Se eliminó la vista previa como fallback de la impresión térmica directa.
- Si no existe impresora predeterminada, el TPV informa el estado sin imprimir el carrito.
- Se redujo el envío BLE a paquetes de 20 bytes con pausa para evitar cortes de ticket.

### 2026-08-01 - Modelo SaaS e-Bar

- Se definió e-Bar como plataforma para bares escolares.
- Se definió `super_admin` como propietario global de la plataforma.
- Se definió `admin_bar` como administrador y cajero autorizado de cada bar.
- Se definió `cajero` como usuario exclusivo del POS.
- Se incorporó al plan la venta, vencimiento y límites de membresías.

### 2026-08-01 - Unidad 1A

- Se creó autorización global para `super_admin`.
- Se agregó panel inicial de administración de la plataforma.
- Se impidió el acceso al panel de plataforma a usuarios de los bares.
- Se agregó usuario de prueba `superadmin@ebar.com` en el seeder.

### 2026-08-01 - Programación Por Fases

- Se organizaron las actividades faltantes en ocho fases.
- Se definieron dependencias y criterios de cierre por fase.
- La Fase 1 quedó dividida en unidades 1A a 1G.
- La unidad siguiente es 1A: rol global `super_admin`.

### 2026-08-01 - Flujo Operativo

- Se documentó el flujo requerido de cajero, apertura, cobros y cierre.
- Se definió crédito como cuenta por cobrar asociada a la venta, sin clientes permanentes.
- Se actualizó el requerimiento: crédito usará una agenda mínima buscable de clientes.

### 2026-08-01 - Fundación Multi-Tenant

- Se crearon negocios, sucursales y membresías.
- Se agregó contexto de negocio y middleware de aislamiento.
- Se agregaron scopes globales por `negocio_id` a los modelos de dominio.
- Se migró y vinculó el negocio principal existente.
- Se agregaron pruebas de aislamiento entre negocios.
- Queda pendiente el selector de negocio/sucursal y los CRUD administrativos.

### 2026-08-05 - Cierre Operativo De La Fase 1

- Se reorganizó el backlog pendiente de la Fase 1 en orden lógico y dependencias.
- Unidad 1B: se crearon `planes` (con `duracion_dias` y límites de cajeros/cajas/sucursales) y el modelo `Plan`.
- Unidad 1E: se creó `membresias` (negocio → plan, estado, fechas de inicio/vencimiento/renovación) y el modelo `Membresia`, separándolo de la pivot de roles `membresias_negocio`.
- Unidad 1C/1D: CRUD global de bares para `super_admin` (`/plataforma/negocios`) con alta de bar, sucursal principal, admin inicial y configuración por negocio.
- Unidad 1E: renovar, suspender y reactivar membresías; comando `membresias:marcar-vencidas` agendado a diario.
- Bloqueo de acceso: el middleware de tenant ahora niega bares suspendidos o con membresía vencida/inactiva.
- Unidad 1F: selector de negocio tras el login para usuarios con varias membresías y acceso "Cambiar de bar".
- 1.2: CRUD de sucursales por negocio (controlador + vista + trait de tenant en `Sucursal`).
- Se agregaron 10 pruebas de plataforma: CRUD de bares, validaciones, renovación, suspensión, bloqueo por estado, selector y redirección.
- Pendiente 1G: asociar cajas, impresoras, productos y ventas a sucursales, y cierre formal de la fase.

### 2026-08-05 - Fase 2 Base: Cajeros, PIN Y Acceso

- Se agregó `pin` (hasheado) y `esta_activo` a `usuarios`.
- `User` ahora expone `rolEnNegocio()` y `esAdminDelNegocioActual()` usando el rol del pivot `membresias_negocio`.
- Se creó el middleware `rol_negocio:admin_bar` (alias `rol_negocio`) que protege todo el backoffice.
- El POS queda accesible para `cajero` y `admin_bar`; el cajero no ve los botones de Admin/Ventas en el navbar del POS.
- CRUD de cajeros (`/cajeros`) para `admin_bar`: alta con PIN y contraseña, edición, y desactivación sin borrar historial.
- Se aplica el límite de cajeros del plan (`plan.limite_cajeros`) al dar de alta.
- El middleware de tenant ahora distingue "usuario sin membresía" (solo pasa en pruebas) de "membresía inactiva" (bloquea).
- Se agregaron 7 pruebas de cajeros y acceso (10 nuevas en total con la Fase 1).
- Pendiente: PIN de acceso rápido al POS, policies por módulo, auditoría y reportes por cajero.

### 2026-08-05 - Reporte De Ventas Por Cajero

- Se agregó `reportePorCajero` en `ControladorPanel` y la ruta `reportes/cajeros`.
- Agrupa ventas por usuario responsable con subtotal, impuesto e ingresos por período.
- Vista `dashboard/cashier-report` y enlace en el menú de Reportes.
- 31 pruebas en total (1 nueva).

### 2026-08-05 - Cierre De Fases 1 a 4

- **Fase 1.2 / Fase 4**: se añadió `sucursal_id` a `cajas`, `turnos_caja`, `impresoras`, `productos` y `ventas`. El POS filtra el catálogo por la sucursal activa del negocio (productos compartidos si es nulo) y la venta hereda la sucursal del turno/caja.
- **Inicio de sesión**: `ContextoNegocio` ahora expone `sucursalId()`, se establece en el middleware desde la primera sucursal activa.
- **Fase 2 - Permisos**: se crearon `ProductoPolicy`, `VentaPolicy`, `CajaPolicy`, `ReportePolicy` y `ConfiguracionPolicy`; el Controller base usa `AuthorizesRequests`, y un `Gate::before` concede todas las capacidades al `admin_bar`. Se aplicó `authorize()` en productos, ventas, caja, reportes y configuración.
- **Fase 2 - PIN rápido del POS**: pantalla `pos/lock.blade.php` con teclado numérico, ruta `punto_venta.desbloquear` (valida PIN hasheado) y `punto_venta.bloquear`; el login marca `pos_desbloqueado` y se agrega un botón de bloqueo en el POS.
- **Fase 2 - Auditoría**: tabla `auditorias` + modelo + `RegistradorAuditoria`; se registran ajustes manuales, cierres de turno y reaperturas.
- **Fase 3**: se verificó que el efectivo esperado deriva directamente del libro de `movimientos_efectivo` del turno (fondo + ventas + entradas − retiros − gastos); el dashboard ahora alerta existencias bajas según `nivel_minimo`.
- **Fase 4 - Compras**: `proveedores`, `ordenes_compra` y `detalles_orden_compra`; CRUD de proveedores y órdenes con recepción de mercancía que incrementa existencias y genera `MovimientoInventario` con referencia `OrdenCompra`.
- **Fase 4 - Conteos**: `conteos_inventario` y `detalles_conteo`; crear conteo con existencias reales y aplicar diferencias como movimientos `ajuste`.
- **Fase 4 - CSV y etiquetas**: `productos.exportar` (descarga CSV) e `productos.importar` (carga con alta/actualización), vista de impresión de etiquetas de precios.
- Menú lateral ampliado: Historial, Conteos, Proveedores, Órdenes de compra, Sucursales, Cajeros y Arqueos.
- 34 pruebas en total pasan.

### 2026-08-05 - Cierre De Brechas Auditadas (Fases 1-4)

- **Selector de sucursal**: el login ahora permite elegir sucursal cuando un negocio tiene varias; el middleware conserva la sucursal seleccionada (si es válida y activa) en vez de auto-asignar la primera; ruta `negocio.sucursal.cambiar` y selector en el navbar del POS.
- **Límites por plan**: `limite_sucursales` validado en `ControladorSucursales@store`; `limite_cajas` validado en el nuevo CRUD de cajas.
- **CRUD de cajas**: nuevo `ControladorCajas` + vista `/cajas` para crear/editar/desactivar cajas con asignación de sucursal y control de `limite_cajas`; la apertura de turno acepta `caja_id`.
- **Reporte de turno**: nueva ruta `caja.turno-detalle` con detalle del cierre: resumen (fondo+ventas+entradas, salidas, esperado/contado/diferencia), ventas del turno y movimientos de efectivo; botón "Detalle" en arqueos.
- **Arqueos por caja y sucursal**: se añadieron filtros de `caja_id` y `sucursal_id` y la columna sucursal en `caja/arqueos`.
- **Vista de auditorías**: `ControladorAuditorias` + `/auditorias` con filtros por módulo/acción, mostrando usuario, acción, descripción e IP.
- 34 pruebas pasan.

### 2026-08-05 - Unidad Descuentos (Fase 5)

- **Migración** `2026_08_05_120000_add_descuentos_table`: `productos.descuento` (%), `detalles_venta.descuento` (monto congelado), `ventas.descuento` (total) y `ventas.descuento_porcentaje` (% comprobante).
- **Modelos**: `Producto` (`descuento` fillable/cast), `DetalleVenta` (`descuento`), `Venta` (`descuento`, `descuento_porcentaje`).
- **ServicioCobro**: descuento por producto automático desde `producto.descuento`; descuento de comprobante vía `descuento` en petición; ambos se congelan en `detalles_venta.descuento` y `ventas.descuento/descuento_porcentaje`.
- **ControladorPuntoVenta@cobrar**: valida y pasa `descuento` (0-100) al servicio.
- **Formularios de producto**: campo "Descuento (%)" en crear/editar/CSV.
- **POS**: input "Descuento por comprobante (%)" en modal de cobro; JS recalcula subtotal/descuento/IVA/total y cambio; envía `descuento` en el payload.
- **Tickets**: líneas de descuento en térmico 58 mm, A4/A5 HTML y fallback 58 mm HTML.
- **Test**: `test_checkout_aplica_descuento_por_producto_y_por_comprobante` verifica línea + comprobante + congelado en detalle.
- 35 pruebas pasan.

### 2026-08-05 - Reembolsos Y Devoluciones (Fase 5, COMPLETADO)

- Migración `2026_08_05_130000_create_reembolsos_table`: `reembolsos` (negocio, sucursal, venta, usuario, tipo parcial/total, monto, motivo, método, autorizado_por) y `reembolsos_detalles` (reembolso, detalle_venta, cantidad, monto).
- Modelos `Reembolso` y `ReembolsoDetalle`; relación `Venta::reembolsos()`.
- `ServicioReembolso@crear`: reembolso total/parcial que valida la cantidad disponible por línea (resta lo ya devuelto), revierte existencias con `MovimientoInventario` tipo `devolucion` (referencia `Reembolso`) y, si el turno está abierto y es en efectivo, genera `MovimientoEfectivo` tipo `retiro` para el cuadre.
- Autorización: política `VentaPolicy::reembolsar` restringe a `admin_bar`; la ruta `reembolsos.crear` va dentro del grupo `rol_negocio:admin_bar` (un cajero recibe 403).
- `ControladorReembolsos`: índice de reembolsos + procesamiento; auditoría (`RegistradorAuditoria`) de cada reembolso.
- UI: botón "Reembolsar / Devolver" y modal (total/parcial, método, motivo, cantidades por artículo) en `sales/show`; enlace "Reembolsos" en el menú lateral.
- Pruebas: reembolso total revierte existencias y efectivo, reembolso parcial respeta disponible, y reembolso exige admin del bar.
- 38 pruebas pasan.

### 2026-08-05 - Verificación Y Accesos De Prueba

- Se limpió la caché (`optimize:clear`) y se aplicaron las migraciones sin `migrate:fresh`.
- Se verificó que la BD seedeada contiene `admin@ebar.com` (admin_bar) y `superadmin@ebar.com` (super_admin), ambos con contraseña de fábrica `password`.
- Se configuró el PIN rápido del POS **1234** para `admin@ebar.com`.
- Suite completa en verde: 38 pruebas / 132 aserciones.
