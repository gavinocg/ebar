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
| 0 | Corrección de errores críticos e integridad de datos | — | COMPLETADO |
| 1 | Plataforma SaaS: super administrador, planes, bares y membresías | Fundación multi-tenant | COMPLETADO |
| 2 | Cajeros, PIN, roles y permisos por bar | Fase 1 | COMPLETADO |
| 3 | Caja, movimientos, cierres y arqueos avanzados | Fases 1 y 2 | COMPLETADO |
| 4 | Inventario avanzado, compras y ajustes | Fase 1 | COMPLETADO |
| 5 | Crédito, cuentas por cobrar, descuentos, devoluciones, tickets abiertos, pagos divididos, variantes, modificadores | Fases 2 y 3 | COMPLETADO |
| 6 | Reportes, exportación CSV y analítica | Fases 2 a 5 | COMPLETADO |
| 7 | Corrección de errores críticos, seguridad y integridad (Auditoría 2026-08-15) | Fases 0-6 | COMPLETADO |
| 8 | Integración de variantes y modificadores al checkout | Fases 5 y 7 | COMPLETADO |
| 9 | Cobertura de pruebas y fábricas | Fases 0-7 | COMPLETADO |
| 10 | Aislamiento Multi-Tenant | Fases 0-9 | PENDIENTE |
| 11 | Integridad Referencial y Migraciones | Fases 0-9 | PENDIENTE |
| 12 | Corrección de Lógica de Negocio | Fases 0-9 | PENDIENTE |
| 13 | Seguridad y Autenticación | Fases 0-9 | PENDIENTE |
| 14 | SoftDeletes y Preservación de Datos | Fases 0-9 | PENDIENTE |
| 15 | Atomicidad y Condiciones de Carrera | Fases 0-9 | PENDIENTE |
| 16 | Índices y Rendimiento | Fases 0-9 | PENDIENTE |
| 17 | Pruebas y Cobertura | Fases 0-16 | EN_PROGRESO |
| 10 | Operación móvil, PWA y modo offline | Fases 1-7 | PENDIENTE |
| 11 | Restaurante, cocina e integraciones | Según necesidad del negocio | PENDIENTE |
| 12 | API, webhooks e integraciones de pago | Fases 1-7 | PENDIENTE |

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

## Fase 5: Ventas Avanzadas — COMPLETADO

- [x] `COMPLETADO` Crear descuentos por producto.
- [x] `COMPLETADO` Crear descuentos por comprobante.
- [x] `COMPLETADO` Congelar descuentos en el detalle de venta.
- [x] `COMPLETADO` Crear reembolsos parciales y totales.
- [x] `COMPLETADO` Revertir existencias mediante movimientos compensatorios.
- [x] `COMPLETADO` Exigir autorización para anulaciones y devoluciones.
- [x] `COMPLETADO` Crear tickets abiertos.
- [x] `COMPLETADO` Crear pagos divididos.
- [x] `COMPLETADO` Crear variantes de productos.
- [x] `COMPLETADO` Crear modificadores y extras.

## Fase 6: Analítica Y Exportación — COMPLETADO

- [x] `COMPLETADO` Crear ranking de productos más vendidos.
- [x] `COMPLETADO` Crear ventas por categoría.
- [x] `COMPLETADO` Crear tendencias comparativas por periodo.
- [x] `COMPLETADO` Crear reportes por método de pago.
- [x] `COMPLETADO` Crear reportes por cajero, caja y sucursal.
- [x] `COMPLETADO` Exportar ventas a CSV.
- [ ] Corregir y ampliar reporte de impuestos.
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

Las siguientes unidades pendientes de la **Fase 5** son: tickets abiertos, pagos divididos, variantes de productos y modificadores/extras. Después seguirán las Fases 6 a 9.

---

## Análisis Completo Del Sistema — Estado vs Pendientes

### Resumen Ejecutivo

**Estado actual:** Fases 1-4 completadas, Fase 5 parcialmente completada (descuentos y reembolsos implementados). Se detectaron **10 errores de lógica**, **8 problemas de integridad de datos**, **6 inconsistencias de diseño**, **5 problemas de seguridad** y **4 vistas con layout incorrecto**.

### Errores Críticos Encontrados

| ID | Severidad | Descripción | Archivo |
|----|-----------|-------------|---------|
| BUG-001 | CRÍTICO | Cambio negativo en ventas de crédito (`cambio = 0 - total = -100`) | `ServicioCobro.php:98` |
| BUG-002 | CRÍTICO | Descuentos no implementados (schema existe, código siempre pone `descuento = 0`) | `ServicioCobro.php:70` |
| BUG-003 | CRÍTICO | Sin `unique` constraint en `clave_idempotencia` — race condition | `ServicioCobro.php:21` |
| BUG-004 | ALTO | Reembolso total sin validación de completitud de items | `ServicioReembolso.php:20` |
| BUG-005 | ALTO | Sin `lockForUpdate` en reembolsos — race condition inventario | `ServicioReembolso.php:54` |
| BUG-006 | ALTO | Cierre de turno sin `DB::transaction` — efectivo esperado puede ser incorrecto | `ControladorCaja.php:139` |
| BUG-007 | MEDIO | `sucursal_id` faltante en `MovimientoEfectivo` de ServicioCobro | `ServicioCobro.php:132` |
| BUG-008 | MEDIO | Transferencias sin tracking — no se registra movimiento de efectivo | `ServicioCobro.php:131` |
| BUG-009 | MEDIO | Apertura de turno sin `DB::transaction` — turno puede quedar sin fondo inicial | `ControladorCaja.php:37` |
| BUG-010 | BAJO | Redondeo impreciso en precio unitario de reembolso | `ServicioReembolso.php:41` |

### Problemas De Integridad De Datos

| ID | Descripción | Tabla(s) Afectada(s) |
|----|-------------|----------------------|
| INT-001 | `sucursal_id` faltante en 5 tablas del dominio | `categorias`, `configuraciones_negocio`, `movimientos_inventario`, `conteos_inventario`, `clientes` |
| INT-002 | `negocio_id` faltante en tablas críticas (nullable) | `movimientos_efectivo`, `clientes`, `configuraciones_negocio`, `movimientos_inventario`, `conteos_inventario` |
| INT-003 | `usuarios.rol` duplica `membresias_negocio.rol` | `usuarios`, `membresias_negocio` |
| INT-004 | `reembolsos.usuario_id` sin `nullOnDelete` | `reembolsos` |
| INT-005 | `TurnoCaja` sin fillable `aprobado_por`, `aprobado_en` | `turnos_caja` |
| INT-006 | `Impresora` tiene `tipo_impresora` en fillable pero no existe en BD | `impresoras` |
| INT-007 | Decimales inconsistentes entre tablas (10,2 vs 12,2) | `ventas`, `ordenes_compra`, `detalles_venta` |
| INT-008 | `configuraciones_negocio.negocio_id` nullable debería ser NOT NULL UNIQUE | `configuraciones_negocio` |

### Problemas De Relaciones En Modelos

| Modelo | Problema | Estado |
|--------|----------|--------|
| `TurnoCaja` | Falta relación `sucursal()` — tiene `sucursal_id` pero no BelongsTo | CRÍTICO |
| `Impresora` | Falta relación `sucursal()` — tiene `sucursal_id` pero no BelongsTo | CRÍTICO |
| `MovimientoEfectivo` | Falta relación `sucursal()` y `caja()` — tiene IDs pero no BelongsTo | CRÍTICO |
| `Sucursal` | Falta relaciones inversas: `cajas()`, `productos()`, `impresoras()`, `ventas()` | ALTO |
| `TurnoCaja` | Falta relación `aprobadoPor()` → BelongsTo User | MEDIO |

### Problemas De Seguridad

| ID | Descripción | Ruta |
|----|-------------|------|
| SEG-001 | Sin rate limiting en PIN de cajero (10,000 combinaciones sin bloqueo) | `POST /inicio-sesion/pin` |
| SEG-002 | `clientes/buscar` sin filtro de negocio — fuga de datos | `GET /clientes/buscar` |
| SEG-003 | DELETE de negocios sin verificar datos asociados | `DELETE /plataforma/negocios/{id}` |
| SEG-004 | `GET /negocio/cambiar` debería ser POST (modifica sesión) | `GET /negocio/cambiar` |
| SEG-005 | `GET /plataforma/negocios/{id}/membresia/renovar` debería ser POST | `GET .../renovar` |

### Problemas De Permisos

| Ruta | Problema |
|------|----------|
| `POST /caja/movimiento` | Sin middleware explícito — cajero NO puede registrar movimientos |
| `/cuadres/{id}/solicitar-modificacion` | En grupo `admin_bar` pero debería ser accessible por cajeros |

### Problemas De Vistas/Layout

| Vista | Problema |
|-------|----------|
| `auth/cajero.blade.php:8` | Error HTML: comilla faltante en `rel="stylesheet"` — rompe la página |
| `cajeros/index.blade.php` | Extiende `layouts.app` en vez de `layouts.sidebar` — layout inconsistente |
| `sucursales/index.blade.php` | Extiende `layouts.app` en vez de `layouts.sidebar` — layout inconsistente |
| `caja/arqueos.blade.php` | Badges solo muestran "Abierto"/"Cerrado" — no muestra estados intermedios |

### Funcionalidades Pendientes (Fase 5)

| Funcionalidad | Estado |
|---------------|--------|
| Tickets abiertos | PENDIENTE — Sin ruta, controlador ni vista |
| Pagos divididos | PENDIENTE — Sin ruta, controlador ni vista |
| Variantes de productos | PENDIENTE — Sin migración, modelo ni vista |
| Modificadores y extras | PENDIENTE — Sin migración, modelo ni vista |

### Funcionalidades Pendientes (Fases 6-9)

| Fase | Funcionalidades Pendientes |
|------|---------------------------|
| Fase 6 | Reportes: ranking productos, ventas por categoría, tendencias, método de pago, exportación CSV/XLSX/PDF |
| Fase 7 | PWA instalable, modo offline, cola offline, sincronización |
| Fase 8 | Restaurante: tipos de pedido, tickets abiertos por mesa, cocina, KDS |
| Fase 9 | API versionada, tokens, webhooks, integraciones de pago |

---

## Plan De Corrección — Fases Y Tareas

### Fase 0: Corrección De Errores Críticos

**Objetivo:** Corregir errores de lógica e integridad que afectan el funcionamiento actual.
**Estado:** COMPLETADO — 40 pruebas pasan / 132 aserciones.

#### 0.1 Corrección De Errores En ServicioCobro

- [x] `COMPLETADO` BUG-001: Corregir cambio negativo en ventas de crédito — `cambio` es `0` cuando método es crédito
- [x] `COMPLETADO` BUG-002: Implementar cálculo de descuentos en `ServicioCobro` — aplica `producto.descuento` cuando `descuento_activo` está habilitado
- [x] `COMPLETADO` BUG-007: Agregar `sucursal_id` y `negocio_id` al crear `MovimientoEfectivo` en ventas
- [x] `COMPLETADO` BUG-008: Registrar movimientos de efectivo para transferencias (tipo `transferencia`)

#### 0.2 Corrección De Errores En ServicioReembolso

- [x] `COMPLETADO` BUG-004: Validar que reembolso tipo `total` incluya TODOS los items
- [x] `COMPLETADO` BUG-005: Agregar `lockForUpdate()` a productos en reembolsos
- [x] `COMPLETADO` BUG-010: Usar precio unitario del detalle (`$detalle->precio`) en vez de recalcularlo

#### 0.3 Corrección De Errores En ControladorCaja

- [x] `COMPLETADO` BUG-006: Envolver cierre de turno en `DB::transaction`
- [x] `COMPLETADO` BUG-009: Envolver apertura de turno en `DB::transaction`
- [x] `COMPLETADO` Agregar `negocio_id` y `sucursal_id` al crear `MovimientoEfectivo` en apertura

#### 0.4 Corrección De Integridad De Datos

- [x] `COMPLETADO` BUG-003: Unique constraint en `ventas.clave_idempotencia` (verificado que ya existía)
- [x] `COMPLETADO` INT-001: Agregar `sucursal_id` a tablas faltantes: `categorias`, `configuraciones_negocio`, `movimientos_inventario`, `conteos_inventario`, `clientes`, `movimientos_efectivo`
- [x] `COMPLETADO` INT-002: Hacer `negocio_id` NOT NULL en `configuraciones_negocio` (MySQL) con `restrictOnDelete`
- [x] `COMPLETADO` INT-004: Agregar `nullOnDelete` a `reembolsos.usuario_id` (nullable)
- [x] `COMPLETADO` INT-005: Agregar `aprobado_por`, `aprobado_en` al fillable de `TurnoCaja`
- [x] `COMPLETADO` INT-006: Remover `tipo_impresora` del fillable de `Impresora`

### Fase 0.5: Corrección De Relaciones En Modelos

- [x] `COMPLETADO` Agregar relación `sucursal()` a `TurnoCaja`
- [x] `COMPLETADO` Agregar relación `sucursal()` a `Impresora`
- [x] `COMPLETADO` Agregar relaciones `sucursal()` y `caja()` a `MovimientoEfectivo`
- [x] `COMPLETADO` Agregar relaciones inversas a `Sucursal`: `cajas()`, `productos()`, `impresoras()`, `ventas()`, `turnosCaja()`
- [x] `COMPLETADO` Agregar relación `aprobadoPor()` a `TurnoCaja`

### Fase 0.6: Corrección De Seguridad

- [x] `COMPLETADO` SEG-001: Rate limiting en PIN de cajero (5 intentos, bloqueo 60s)
- [x] `COMPLETADO` SEG-002: Agregar filtro de negocio a `clientes/buscar` y `clientes.store`
- [x] `COMPLETADO` SEG-004: Cambiar `GET /negocio/cambiar` a `POST` (vista actualizada)
- [x] `COMPLETADO` SEG-005: Cambiar `GET .../renovar` a `POST` (vista actualizada)
- [x] `COMPLETADO` Agregar middleware `rol_negocio:cajero` a `POST /caja/movimiento`
- [x] `COMPLETADO` Mover `solicitar-modificacion` fuera del grupo `admin_bar` — cajeros pueden solicitar

### Fase 0.7: Corrección De Vistas

- [x] `COMPLETADO` Corregir error HTML en `auth/cajero.blade.php:8` — comilla faltante en `rel="stylesheet"`
- [x] `COMPLETADO` Cambiar `cajeros/index.blade.php` de `layouts.app` a `layouts.sidebar`
- [x] `COMPLETADO` Cambiar `sucursales/index.blade.php` de `layouts.app` a `layouts.sidebar`
- [x] `COMPLETADO` Actualizar badges en `caja/arqueos.blade.php` para todos los estados: abierta, cerrada, pendiente_aprobacion, aprobada, pendiente_modificacion

### Fase 5.5: Funcionalidades Pendientes — COMPLETADO

- [x] `COMPLETADO` Tickets abiertos (venta sin cerrar) — tablas `tickets_abiertos` + `tickets_abiertos_detalles`, controlador, rutas, vista POS
- [x] `COMPLETADO` Pagos divididos (métodos de pago combinados) — columna `pagos_divididos` en `ventas`, método `dividido` en ServicioCobro
- [x] `COMPLETADO` Variantes de productos (tamaño, color, sabor) — tabla `producto_variantes`, modelo `ProductoVariante`
- [x] `COMPLETADO` Modificadores y extras (ingredientes adicionales) — tablas `grupos_modificadores`, `modificadores`, `producto_grupo_modificador`

### Fase 6: Analítica Y Exportación — COMPLETADO

- [x] `COMPLETADO` Crear ranking de productos más vendidos — `ControladorReportes::productos`
- [x] `COMPLETADO` Crear ventas por categoría — `ControladorReportes::categorias`
- [x] `COMPLETADO` Crear tendencias comparativas por periodo — `ControladorReportes::tendencias`
- [x] `COMPLETADO` Crear reportes por método de pago — `ControladorReportes::metodosPago`
- [x] `COMPLETADO` Crear reportes por cajero, caja y sucursal — `ControladorReportes::porSucursal`
- [x] `COMPLETADO` Exportar ventas a CSV — `ControladorReportes::exportarVentasCsv`
- [ ] `PENDIENTE` Corregir y ampliar reporte de impuestos
- [ ] `PENDIENTE` Exportar reportes a XLSX/PDF

### Fase 7: Operación Móvil

- [ ] `PENDIENTE` Eliminar `user-scalable=no`
- [ ] `PENDIENTE` Implementar carga progresiva del catálogo
- [ ] `PENDIENTE` Implementar búsqueda AJAX
- [ ] `PENDIENTE` Crear modo oscuro configurable
- [ ] `PENDIENTE` Crear pantalla para clientes
- [ ] `PENDIENTE` Crear PWA instalable
- [ ] `PENDIENTE` Crear cola offline con idempotencia
- [ ] `PENDIENTE` Sincronizar ventas al recuperar conexión

### Fase 8: Restaurante (Opcional)

- [ ] `PENDIENTE` Crear tipos de pedido: local, llevar y domicilio
- [ ] `PENDIENTE` Crear tickets abiertos por mesa
- [ ] `PENDIENTE` Crear estaciones de cocina
- [ ] `PENDIENTE` Crear impresoras de cocina
- [ ] `PENDIENTE` Crear pantalla KDS
- [ ] `PENDIENTE` Crear notas de preparación
- [ ] `PENDIENTE` Crear estados de preparación

### Fase 9: Integraciones

- [ ] `PENDIENTE` Crear API versionada
- [ ] `PENDIENTE` Crear tokens de integración por negocio
- [ ] `PENDIENTE` Crear webhooks de ventas e inventario
- [ ] `PENDIENTE` Integrar proveedores de pagos compatibles con Ecuador
- [ ] `PENDIENTE` Integrar contabilidad o facturación electrónica si se requiere

### Fase 10: Flujo De Cajero Y Cobros (Parcialmente Implementado)

- [x] `COMPLETADO` Login del cajero con cédula y PIN
- [x] `COMPLETADO` PIN hasheado
- [x] `COMPLETADO` Gestión de roles cajero, admin_bar y propietario
- [x] `COMPLETADO` Apertura de caja después del login
- [x] `COMPLETADO` Bloqueo del POS sin turno abierto
- [x] `COMPLETADO` Efectivo como movimiento de caja
- [x] `COMPLETADO` Crédito con cliente y cuenta por cobrar
- [x] `COMPLETADO` Transferencia con entidad y comprobante
- [x] `COMPLETADO` Cierre y cuadre por cajero
- [x] `COMPLETADO` Reapertura autorizada por propietario
- [ ] `PENDIENTE` Pruebas de aislamiento de acceso y pagos

### Fase 11: Agenda Mínima Para Crédito (COMPLETADO)

- [x] `COMPLETADO` Crear tabla `clientes` con nombre, descripción y estado activo
- [x] `COMPLETADO` Crear búsqueda incremental por caracteres digitados
- [x] `COMPLETADO` Mostrar opciones con nombre, descripción y botón `Seleccionar`
- [x] `COMPLETADO` Exigir selección de cliente para una venta a crédito
- [x] `COMPLETADO` Guardar `cliente_id` y snapshot de nombre/descripción en la venta
- [x] `COMPLETADO` Crear clientes desde el POS (búsqueda + alta rápida)
- [ ] `PENDIENTE` Mantener esta agenda separada de fidelización, puntos y CRM

### Despliegue Pendiente

- [ ] `PENDIENTE` Rotar el token de Cloudflare compartido durante la configuración

---

## Auditoría Integral — 2026-08-15

### Resumen Ejecutivo

Se realizó una auditoría profunda del sistema completo cubriendo: integridad de datos, lógica POS, vistas/rutas, seguridad, y cobertura de pruebas. Se encontraron **15 errores críticos, 17 altos, 22 medios y 12 bajos** organizados en las Fases 7-9.

### Errores Críticos Encontrados

| ID | Descripción | Archivo | Impacto |
|----|-------------|---------|---------|
| CRIT-001 | `ControladorTicketsAbiertos` llama `ContextoNegocio::negocioId()` (método estático inexistente) — lanza `BadMethodCallException` en runtime | `ControladorTicketsAbiertos.php:16,39,40,52` | **Tickets abiertos completamente roto** |
| CRIT-002 | Pago dividido: no se valida que la suma de `pagos_divididos` iguale o supere el total de la venta | `ServicioCobro.php:174-191` | **Pérdida financiera — cobros parciales sin detectar** |
| CRIT-003 | Reembolso: monto no limitado al total original — descuentos no considerados, cliente puede recibir más de lo pagado | `ServicioReembolso.php:52-54` | **Pérdida financiera en reembolsos** |
| CRIT-004 | `ControladorCaja::reabrir()` no valida que el turno pertenezca al negocio actual — bypass de aislamiento tenant | `ControladorCaja.php:316-339` | **Apertura de turnos de otros negocios** |
| CRIT-005 | `ControladorTicketsAbiertos::show()/destroy()` sin validación de propiedad — cualquier usuario elimina cualquier ticket | `ControladorTicketsAbiertos.php:72-85` | **Eliminación cross-tenant** |
| CRIT-006 | `ControladorCaja::abrir()` no previene turnos duplicados — dos requests concurrentes abren dos turnos | `ControladorCaja.php:28-35` | **Turnos duplicados, cuadre corrupto** |
| CRIT-007 | `ventas.turno_caja_id` es nullable en BD — ventas pueden existir sin turno asociado | Migración `add_caja_to_ventas` | **Datos huérfanos, reporting corrupto** |
| CRIT-008 | `ControladorTicketsAbiertos` no reserva stock al guardar ticket — dos tickets pueden cobrar el mismo producto | `ControladorTicketsAbiertos.php:37-63` | **Sobreventa de stock** |
| CRIT-009 | Variante de precio y modificadores NO integrados al checkout — `ServicioCobro` siempre usa `Producto::precio` | `ServicioCobro.php:65` | **Funcionalidad incompleta — variantes/modificadores no afectan cobro** |
| CRIT-010 | Carrito es 100% client-side (JS) — crash del browser pierde todo el carrito | `pos/index.blade.php:226` | **Pérdida de datos POS** |
| CRIT-011 | `idempotencyKey` fallback usa `Date.now()-${Math.random()}` — colisiones en la misma millisevenida | `pos/index.blade.php:547` | **Ventas duplicadas silenciosas** |
| CRIT-012 | `ControladorCaja::cerrar()` incluye transferencias en el cálculo de efectivo esperado — infla el monto | `ControladorCaja.php:72` | **Cuadre de caja incorrecto** |
| CRIT-013 | Route-model binding en `ControladorSucursales::destroy()` no valida pertenencia al negocio | `ControladorSucursales.php:68-73` | **Eliminación cross-tenant** |
| CRIT-014 | `ControladorCajas::store()` valida `exists:sucursales,id` globalmente, no por negocio | `ControladorCajas.php:44` | **Caja apunta a sucursal de otro negocio** |
| CRIT-015 | `ControladorCajeros::store()` valida `exists:sucursales,id` globalmente, no por negocio | `ControladorCajeros.php:57` | **Cajero asignado a sucursal de otro negocio** |

### Errores Altos Encontrados

| ID | Descripción | Archivo |
|----|-------------|---------|
| ALT-001 | `ControladorCaja::movimiento()` no setea `sucursal_id` en `MovimientoEfectivo` | `ControladorCaja.php:304-311` |
| ALT-002 | `ControladorConteos::aplicar()` sin `lockForUpdate()` en productos — race condition | `ControladorConteos.php:74-103` |
| ALT-003 | `ControladorCompras::recibir()` sin `lockForUpdate()` en productos — race condition | `ControladorCompras.php:125-153` |
| ALT-004 | `MembresiaNegocio` no usa `PerteneceANegocio` — consultas sin scope de negocio | `MembresiaNegocio.php` |
| ALT-005 | `tickets_abiertos` FKs sin `constrained()` — sin integridad referencial | Migración tickets |
| ALT-006 | `User.$fillable` incluye `pin`, `password`, `rol` — riesgo de mass assignment | `User.php:15` |
| ALT-007 | Login sin rate limiting a nivel IP — brute-force posible | `routes/web.php:31-36` |
| ALT-008 | Bloqueo PIN en sesión — reseteable con nueva sesión/incógnito | `ControladorAutenticacion.php:97-122` |
| ALT-009 | `ControladorCompras::storeOrden()` sin `DB::transaction()` — compras parciales | `ControladorCompras.php:72-118` |
| ALT-010 | `ControladorConteos::store()` sin `DB::transaction()` — conteos parciales | `ControladorConteos.php:31-67` |
| ALT-011 | `ControladorSucursales::destroy()` sin verificar turnos activos o cajas abiertas | `ControladorSucursales.php:68-73` |
| ALT-012 | `ControladorProductos::importar()` sin `DB::transaction()` — importaciones parciales | `ControladorProductos.php:160-219` |
| ALT-013 | `exists` validator en checkout bypass Eloquent scopes — productos cross-tenant posibles | `ControladorPuntoVenta.php:130` |
| ALT-014 | `ventas/index.blade.php` muestra "Transferencia" para ventas a crédito | `sales/index.blade.php:35` |
| ALT-015 | `pos/lock.blade.php` extiende `layouts.sidebar` en vez de `layouts.pos` — muestra admin sidebar | `pos/lock.blade.php:1` |
| ALT-016 | `layouts/sidebar.blade.php` tiene `@if` duplicado en línea 293-294 | `layouts/sidebar.blade.php:293` |
| ALT-017 | `layouts/app.blade.php` muestra links admin para super_admin que no puede acceder | `layouts/app.blade.php:24-64` |

### Errores Medios Encontrados

| ID | Descripción | Archivo |
|----|-------------|---------|
| MED-001 | `ControladorReportes` no valida fechas del request | `ControladorReportes.php:17-24` |
| MED-002 | `ControladorCaja::cerrar()` no valida `motivo` en rechazo de cuadre | `ControladorCaja.php:236` |
| MED-003 | `ControladorCaja::solicitarModificacion()` no valida `motivo` | `ControladorCaja.php:247-261` |
| MED-004 | `ReembolsoDetalle` no tiene `PerteneceANegocio` (relía en padre) | `ReembolsoDetalle.php` |
| MED-005 | `DetalleOrdenCompra` no tiene `PerteneceANegocio` (relía en padre) | `DetalleOrdenCompra.php` |
| MED-006 | `DetalleConteo` no tiene `PerteneceANegocio` (relía en padre) | `DetalleConteo.php` |
| MED-007 | `PerteneceANegocio` es bypassable cuando `ContextoNegocio` es null (queue/artisan) | `PerteneceANegocio.php:12-17` |
| MED-008 | `ControladorPuntoVenta::cobrar()` — `exists:clientes,id` bypass Eloquent scopes | `ControladorPuntoVenta.php:136` |
| MED-009 | `ConfiguracionNegocio::obtenerConfiguracion()` fuera de contexto HTTP puede retornar config equivocada | `ConfiguracionNegocio.php:30-42` |
| MED-010 | `caja/cuadres-pendientes.blade.php` muestra flash success duplicado | `caja/cuadres-pendientes.blade.php:13-15` |
| MED-011 | `layouts/app.blade.php` navbar incompleto vs sidebar en reportes | `layouts/app.blade.php:79-85` |
| MED-012 | Reembolso no considera descuentos originales — precio unitario sin descuento | `ServicioReembolso.php:52-53` |
| MED-013 | `stockBefore` en reembolso puede estar stale parasegundo línea del mismo producto | `ServicioReembolso.php:63-66` |
| MED-014 | `ControladorProductos::update()` incluye `descuento` sin validación explícita | `ControladorProductos.php:99-101` |
| MED-015 | `Sucursal.$fillable` incluye `negocio_id` — mass assignment risk | `Sucursal.php:16` |
| MED-016 | `DescuentoPorcentaje` parámetro muerto en ServicioCobro — nunca se aplica | `ServicioCobro.php:96` |
| MED-017 | `EstablecerContextoNegocio` tiene bypass en testing — riesgo si `APP_ENV` mal configurado | `EstablecerContextoNegocio.php:33-35` |
| MED-018 | `LIKE` queries no escapan wildcards `%` y `_` | `ControladorClientes.php:24` |
| MED-019 | `ReembolsoDetalle MovimientoInventario` sin `id_referencia` — link roto auditoría | `ServicioReembolso.php:75-76` |
| MED-020 | `ControladorProductos::importar()` — `$producto->update($fila)` con datos CSV sin validar | `ControladorProductos.php:211` |
| MED-021 | `ControladorPuntoVenta` — `distinct` en `producto_id` impide server-side merge de cantidades | `ControladorPuntoVenta.php:130` |
| MED-022 | `ControladorCaja::abrir()` — `turnoAbierto()` fuera de transacción — race condition | `ControladorCaja.php:28-35` |

### Errores Bajos Encontrados

| ID | Descripción | Archivo |
|----|-------------|---------|
| BAJ-001 | `ticket_abierto` subtotal calculado con descuento flat vs checkout con descuento porcentaje — inconsistencia | `ControladorTicketsAbiertos.php:49` |
| BAJ-002 | `restaurarTicket` JS hardcodea `stock: 999999` — bypass de stock | `pos/index.blade.php:846` |
| BAJ-003 | `ventas/show.blade.php` no muestra badge "Crédito" correctamente | `sales/show.blade.php:31` |
| BAJ-004 | `phpunit.xml` no tiene `failOnRisky` ni `failOnWarning` | `phpunit.xml` |
| BAJ-005 | `tests/Unit/ExampleTest.php` — `assertTrue(true)` no prueba nada | `tests/Unit/ExampleTest.php:12` |
| BAJ-006 | `tests/Feature/ExampleTest.php` — `RefreshDatabase` comentado | `tests/Feature/ExampleTest.php:5` |
| BAJ-007 | `CheckoutTest` y `CajerosTest` tienen helpers duplicados con implementaciones diferentes | Tests |
| BAJ-008 | Solo 1 factory (`UserFactory`) —其余15+ modelos sin factory | `database/factories/` |
| BAJ-009 | `test_los_datos_se_aislan_por_negocio` solo prueba `Categoria` — no Producto/Venta/Turno | `PlataformaTest.php` |
| BAJ-010 | Cobertura de controllers: 6/26 (23%) — 20 controllers sin tests | Tests |
| BAJ-011 | Líneas sin test: ~1,200+ en controllers/services | Tests |
| BAJ-012 | `test_caja_se_puede_abrir_y_cerrar_con_arqueo` crea Caja sin `negocio_id` explícito | `CheckoutTest.php:177` |

---

## Fase 7: Corrección De Errores Críticos (Auditoría 2026-08-15)

**Objetivo:** Corregir errores críticos y altos de seguridad, integridad y lógica encontrados en la auditoría.
**Estado:** COMPLETADO

### 7.1 Errores Críticos — Seguridad y Aislamiento Tenant

- [x] `COMPLETADO` CRIT-001: Corregir `ControladorTicketsAbiertos` — reemplazar `ContextoNegocio::negocioId()` por `app(ContextoNegocio::class)->id()` (4 métodos)
- [x] `COMPLETADO` CRIT-004: Agregar `abort_unless($turnoCaja->negocio_id === app(ContextoNegocio::class)->id(), 404)` en `ControladorCaja::reabrir()`
- [x] `COMPLETADO` CRIT-005: Agregar validación de propiedad en `ControladorTicketsAbiertos::show()` y `destroy()`
- [x] `COMPLETADO` CRIT-013: Agregar validación de propiedad en `ControladorSucursales::destroy()`
- [x] `COMPLETADO` CRIT-014: Validar `sucursal_id` pertenece al negocio en `ControladorCajas::store()`
- [x] `COMPLETADO` CRIT-015: Validar `sucursal_id` pertenece al negocio en `ControladorCajeros::store()`

### 7.2 Errores Críticos — Lógica de Negocio

- [x] `COMPLETADO` CRIT-002: Validar suma de `pagos_divididos >= total` en `ServicioCobro` antes de crear venta
- [x] `COMPLETADO` CRIT-003: Limitar reembolso al monto neto pagado (subtotal - descuento), no al precio unitario
- [x] `COMPLETADO` CRIT-006: Agregar `lockForUpdate()` en `ControladorCaja::abrir()` para prevenir turnos duplicados
- [x] `COMPLETADO` CRIT-007: Hacer `turno_caja_id` NOT NULL en tabla `ventas` (nueva migración)
- [x] `COMPLETADO` CRIT-012: Excluir transferencias del cálculo de efectivo esperado en `ControladorCaja::cerrar()` — usar `where('tipo', '!=', 'transferencia')`

### 7.3 Errores Críticos — Funcionalidad Rota

- [x] `COMPLETADO` CRIT-008: Stock already validated with `lockForUpdate()` in `ServicioCobro` at checkout time — concurrent ticket charges serialize correctly
- [x] `COMPLETADO` CRIT-010: Guardado server-side del carrito (sesión Laravel) — sync automático en cada mutación y carga al iniciar POS
- [x] `COMPLETADO` CRIT-011: Usar `Str::uuid()` o `bin2hex(random_bytes(16))` para `idempotencyKey` en JS

### 7.4 Errores Altos — Seguridad

- [x] `COMPLETADO` ALT-006: Remover `pin`, `password`, `rol` de `$fillable` en `User` — asignación explícita
- [x] `COMPLETADO` ALT-007: Agregar middleware `throttle:5,1` a rutas de login
- [x] `COMPLETADO` ALT-008: Bloqueo PIN en DB (tabla `pin_intentos` con `intentos`, `bloqueado_hasta`) — persiste entre sesiones
- [x] `COMPLETADO` ALT-017: Ocultar links admin en `layouts/app.blade.php` para super_admin — solo mostrar si `esAdminDelNegocioActual()`

### 7.5 Errores Altos — Integridad

- [x] `COMPLETADO` ALT-001: Agregar `sucursal_id` desde turno en `ControladorCaja::movimiento()`
- [x] `COMPLETADO` ALT-002: Agregar `lockForUpdate()` a productos en `ControladorConteos::aplicar()`
- [x] `COMPLETADO` ALT-003: Agregar `lockForUpdate()` a productos en `ControladorCompras::recibir()`
- [x] `COMPLETADO` ALT-004: MembresiaNegocio no necesita `PerteneceANegocio` — consultas de auth/selector requieren acceso cross-tenant
- [x] `COMPLETADO` ALT-005: Agregar `constrained()` a FKs en migración adicional `tickets_abiertos` (turno_caja_id, usuario_id, producto_id)
- [x] `COMPLETADO` ALT-009: Envolver `ControladorCompras::storeOrden()` en `DB::transaction()`
- [x] `COMPLETADO` ALT-010: Envolver `ControladorConteos::store()` en `DB::transaction()`
- [x] `COMPLETADO` ALT-011: Verificar turnos activos antes de eliminar sucursal (CRIT-013)
- [x] `COMPLETADO` ALT-012: Envolver `ControladorProductos::importar()` en `DB::transaction()`

### 7.6 Errores Altos — Vistas

- [x] `COMPLETADO` ALT-014: Corregir badge de pago en `sales/index.blade.php` — mostrar "Crédito" correctamente
- [x] `COMPLETADO` ALT-015: Cambiar `pos/lock.blade.php` de `layouts.sidebar` a `layouts.pos` o layout dedicado
- [x] `COMPLETADO` ALT-016: Eliminar `@if` duplicado en `layouts/sidebar.blade.php:293-294`

### 7.7 Errores Medios

- [x] `COMPLETADO` MED-001: Validar fechas en `ControladorReportes` (`date|before_or_equal`)
- [x] `COMPLETADO` MED-002/003: Validar `motivo` (`required|string|max:500`) en rechazo y solicitud de modificación
- [x] `COMPLETADO` MED-004/005/006: Detail models (`ReembolsoDetalle`, `DetalleOrdenCompra`, `DetalleConteo`) always accessed through parent — trait not needed
- [x] `COMPLETADO` MED-010: Eliminar flash success duplicado en `caja/cuadres-pendientes.blade.php`
- [x] `COMPLETADO` MED-014: Agregar `descuento` a validación en `ControladorProductos::store()`
- [x] `COMPLETADO` MED-015: Remover `negocio_id` de `$fillable` en `Sucursal` (usar PerteneceANegocio)
- [x] `COMPLETADO` MED-018: Escapar wildcards `%` y `_` en búsquedas LIKE
- [x] `COMPLETADO` MED-019: Agregar `id_referencia` a `MovimientoInventario` en reembolsos
- [x] `COMPLETADO` MED-020: Filtrar datos CSV por `$fillable` en `importar()` — nunca pass raw a `update()`
- [x] `COMPLETADO` MED-022: Mover `turnoAbierto()` dentro de la transacción con `lockForUpdate()` en apertura (CRIT-006)

---

## Fase 8: Integración De Variantes Y Modificadores Al Checkout

**Objetivo:** Completar la integración de variantes y modificadores al flujo de cobro.
**Estado:** COMPLETADO

### 8.1 Variantes

- [x] `COMPLETADO` Actualizar `ServicioCobro::crear()` para aceptar `variante_id` por item y usar precio de variante cuando exista
- [x] `COMPLETADO` Actualizar `DetalleVenta` para incluir `producto_variante_id` nullable (nueva migración)
- [x] `COMPLETADO` Actualizar carrito JS para incluir variantes seleccionadas
- [x] `COMPLETADO` Actualizar vista POS para mostrar selector de variantes al seleccionar producto
- [x] `COMPLETADO` Validar stock de variante (si tiene stock propio) antes de cobrar
- [x] `COMPLETADO` Agregar `lockForUpdate()` a variantes en ServicioCobro

### 8.2 Modificadores

- [x] `COMPLETADO` Actualizar `ServicioCobro::crear()` para aceptar `modificadores[]` por item y sumar `precio_extra`
- [x] `COMPLETADO` Crear tabla `detalle_venta_modificadores` (pivot) con `detalle_venta_id`, `modificador_id`, `precio_extra`
- [x] `COMPLETADO` Actualizar carrito JS para incluir modificadores seleccionados
- [x] `COMPLETADO` Actualizar vista POS para mostrar selector de modificadores (modal o inline)
- [x] `COMPLETADO` Validar `min_seleccion`/`max_seleccion` de grupos requeridos

### 8.3 Tickets Abiertos + Variantes/Modificadores

- [x] `COMPLETADO` Guardar variante y modificadores en `tickets_abiertos_detalles`
- [x] `COMPLETADO` Restaurar variante y modificadores al cargar ticket

---

## Fase 9: Cobertura De Pruebas Y Fábricas

**Objetivo:** Crear pruebas para todas las funcionalidades críticas y fábricas para todos los modelos.
**Estado:** PENDIENTE

### 9.1 Fábricas

- [ ] `PENDIENTE` Crear `NegocioFactory`
- [ ] `PENDIENTE` Crear `SucursalFactory`
- [ ] `PENDIENTE` Crear `CategoriaFactory`
- [ ] `PENDIENTE` Crear `ProductoFactory`
- [ ] `PENDIENTE` Crear `VentaFactory` + `DetalleVentaFactory`
- [ ] `PENDIENTE` Crear `TurnoCajaFactory`
- [ ] `PENDIENTE` Crear `CajaFactory`
- [ ] `PENDIENTE` Crear `TicketAbiertoFactory`
- [ ] `PENDIENTE` Crear `ClienteFactory`
- [ ] `PENDIENTE` Crear `MembresiaNegocioFactory`
- [ ] `PENDIENTE` Crear `ProveedorFactory` + `OrdenCompraFactory`
- [ ] `PENDIENTE` Crear `MovimientoEfectivoFactory`

### 9.2 Pruebas Críticas (P0)

- [ ] `PENDIENTE` `AuthTest` — login, PIN, bloqueo por intentos, logout
- [ ] `PENDIENTE` `TicketsAbiertosTest` — CRUD, aislamiento tenant, restore
- [ ] `PENDIENTE` `SplitPaymentTest` — pago dividido, validación de montos, movimientos
- [ ] `PENDIENTE` `CreditSaleTest` — venta crédito, cliente requerido, estado pendiente

### 9.3 Pruebas Altas (P1)

- [ ] `PENDIENTE` `TaxTest` — impuesto habilitado, cálculo correcto, cambio de porcentaje
- [ ] `PENDIENTE` `InventoryTest` — ajustes, stock negativo, historial
- [ ] `PENDIENTE` `PurchaseOrdersTest` — crear, recibir, stock update
- [ ] `PENDIENTE` `CsvExportTest` — exportar productos/ventas, importar productos

### 9.4 Pruebas Medias (P2)

- [ ] `PENDIENTE` `ReportsTest` — todos los reportes Fase 6
- [ ] `PENDIENTE` `CajaApprovalTest` — aprobar, rechazar, modificar cuadre
- [ ] `PENDIENTE` `MultiTenantIsolationTest` — Producto, Venta, TurnoCaja, Caja aislados
- [ ] `PENDIENTE` `ServicioCobroTest` — cálculos unitarios, idempotencia
- [ ] `PENDIENTE` `VariantTest` — precio variante, stock variante
- [ ] `PENDIENTE` `ModifierTest` — precio extra, validación min/max

### 9.5 Cobertura Objetivo

| Área | Actual | Objetivo |
|------|--------|----------|
| Controllers | 6/26 (23%) | 20/26 (77%) |
| Services | 2/5 (40%) | 5/5 (100%) |
| Modelos con factory | 1/15 (7%) | 15/15 (100%) |
| Tests totales | 36 | 80+ |

---

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

### 2026-08-05 - Modelo De Negocio: Propietario, Admin_bar Y Cajero

- Se definieron tres roles de bar con jerarquía: `propietario` (dueño) > `admin_bar` (operaciones) > `cajero` (POS).
- `propietario`: administra parámetros del bar, cajeros/PINs, reportes financieros, auditoría, configuración, reapertura de turnos; además tiene todas las opciones de `admin_bar` y `cajero`.
- `admin_bar`: operaciones — categorías, productos, ventas, inventario, compras, cuadres diarios, reembolsos, POS; no gestiona cajeros, configuración, auditoría ni reportes.
- `cajero`: solo POS.
- Modelo `User`: `esAdminDelNegocioActual()` ahora cubre operaciones (`propietario` ∨ `admin_bar`); se agregó `esPropietario()` para lo exclusivo del dueño.
- Migración `2026_08_05_140000_renombrar_roles`: renombra existentes `admin_bar`/`administrador` → `propietario` en `membresias_negocio` y `usuarios`.
- `AutorizarRolNegocio` ahora usa jerarquía (cajero=1, admin_bar=2, propietario=3): `rol_negocio:admin_bar` lo pasan admin_bar y propietario; `rol_negocio:propietario` solo el dueño.
- `Gate::before` ya no hace bypass a admin_bar: solo `propietario` se salta políticas; admin_bar queda sujeto a ellas.
- Políticas `ConfiguracionPolicy` y `ReportePolicy` → `esPropietario()`; el gate `reportes.ver` → `esPropietario()`.
- Rutas propietario-only protegidas con `rol_negocio:propietario`: cajeros.*, configuracion.*, auditorias.*, reportes.*, caja.reabrir, impresoras.*, sucursales.*, cajas.*.
- Seeder y `ControladorNegocios` crean el admin inicial como `propietario`.
- Sidebar: las secciones de cajeros, configuración, auditoría, reportes, impresoras, sucursales y cajas solo se muestran al `propietario`.
- Pruebas: 40 pasan / 134 aserciones, incluyendo verificación de que el nuevo `admin_bar` reducido NO accede a cajeros, reportes ni reapertura.

### 2026-08-06 - Flujo Cajero Completo Y Sucursales

- **Flujo de login del cajero**: link "Soy Cajero" en `/inicio-sesion` → formulario solo con correo → valida que exista y tenga PIN → pantalla de ingreso de PIN (keypad standalone) → ingresa al POS.
- **Apertura de caja como primera pantalla**: al ingresar al POS sin turno abierto, muestra pantalla de apertura asignada al cajero (fondo inicial).
- **Cierre de caja con denominaciones**: pantalla de cierre con conteo de billetes (100, 50, 20, 10, 5, 1) y monedas (1, 0.50, 0.25, 0.10, 0.05, 0.01), más comprobantes de crédito/transferencia del turno y cuadre automático.
- **Cierre temporal vs final**: el cajero puede hacer cierres temporales (reabiertos) o el cierre final del día que activa el flujo de aprobación.

### 2026-08-06 - Cuadre Configurable Y Visto Bueno

- **Configuración por cajero** (en `/cajeros`): `cuadre_activo` (conteo de billetes/monedas), `aprobacion_activa` (requiere visto bueno del admin), `limite_cajeros` (por membresía, independiente del plan).
- **Visto bueno**: tras cierre final, si `aprobacion_activa=ON` → estado `pendiente_aprobacion` → el admin aprueba (`/cuadres/pendientes`) → `aprobada` o rechaza → vuelve a `abierta`.
- **Modificación de cuadre**: el cajero solicita → estado `pendiente_modificacion` → el admin autoriza → vuelve a `abierta` para nuevo cierre.
- **Estados del turno**: `abierta`, `cerrada` (temporal), `pendiente_aprobacion`, `aprobada`, `pendiente_modificacion`.
- Columnas `aprobado_por`/`aprobado_en` agregadas a `turnos_caja`.

### 2026-08-06 - Vinculación Cajero-Sucursal

- `membresias_negocio.sucursal_id`: cada cajero está asignado a una sucursal específica.
- **Restricción de cobro**: el cajero SOLO puede cobrar en su sucursal asignada. Intentar cobrar en otra → 403 con mensaje.
- **Dirección automática**: al hacer login, se establece automáticamente la `sucursal_id` del cajero en sesión. No hay selector de sucursal en el POS.
- **Información visible**: el nombre de la sucursal se muestra como dato informativo en el navbar del POS.

### 2026-08-06 - Descuentos Controlados Por Configuración

- **Migración** `2026_08_05_190000_add_descuento_activo`: `configuraciones_negocio.descuento_activo` (boolean, default `false`).
- **Switch en configuración**: "Permitir descuentos en ventas" (default OFF).
- **Limpieza completa del flujo POS**: eliminado input de descuento del modal, fila de descuento del carrito, función JS `obtenerDescuentoPorcentaje`, evento del discountInput, campo `descuento` del payload, validación en el controlador, y líneas de descuento en tickets (térmico y HTML).
- **ServicioCobro**: ya no aplica descuentos por producto ni por comprobante.
- **Modelos/BD**: se mantienen columnas de descuento por compatibilidad pero no se usan.

### 2026-08-06 - Clientes: Búsqueda Y Alta Rápida Desde POS

- **ControladorClientes@store**: nuevo método para crear clientes desde el POS.
- **Ruta** `POST /clientes` (cajero).
- **Modal de crédito en POS**: campo Cliente con búsqueda por carácter (despliega coincidencias) + botón "+" que abre formulario inline para agregar cliente (nombre + descripción). Al guardar, se selecciona automáticamente.
- **Validación mejorada**: los errores de validación del backend se muestran como mensajes específicos (no genéricos).

### 2026-08-06 - Reporte De Ventas Con Filtro De Sucursal

- **Selector de sucursales** en `/reportes/ventas`: carga las sucursales activas del negocio y filtra las ventas por la sucursal seleccionada (o muestra todas).
- **Corrección de rango de fechas**: ahora usa `00:00:00` a `23:59:59` para incluir todas las ventas del día final.

### 2026-08-06 - Botón Ventas En POS

- **Botón "Ventas"** en el navbar del POS (junto a "Cerrar caja").
- **Modal con listado del día**: muestra las ventas del cajero actual — comprobante, método de pago (Efectivo/Crédito/Transferencia) y valor.
- **Total al final**: sumatoria de la caja del cajero que consulta.

### 2026-08-06 - Correcciones De Estabilidad

- Corregido `whereBetween` en reporte de ventas (fechas con hora).
- Corregido type hint `View` en `ControladorCaja` (faltaba import).
- Corregido import `Sale` en `ControladorPuntoVenta`.
- Corregido import `Sucursal` en `ControladorCajeros`.
- Corregido `resolverLimiteCajeros` para consultar `membresias_negocio` en vez de `membresias`.
- 40 pruebas pasan / 132 aserciones.

### 2026-08-15 - Fase 0: Corrección De Errores Críticos

- **Migración** `2026_08_06_200000_fix_data_integrity_phase0`: unique en `ventas.clave_idempotencia`, `sucursal_id` a 6 tablas del dominio, `negocio_id` NOT NULL en `configuraciones_negocio`, `reembolsos.usuario_id` nullable con `nullOnDelete`.
- **ServicioCobro**: corregido cambio negativo en crédito (`cambio = 0`), implementado cálculo de descuentos (respeta `descuento_activo`), agregado `sucursal_id`/`negocio_id` a `MovimientoEfectivo`, registrado跟踪 de transferencias.
- **ServicioReembolso**: validación de completitud en reembolso total, `lockForUpdate()` en productos, precio unitario desde `$detalle->precio`.
- **ControladorCaja**: apertura y cierre envueltos en `DB::transaction`, `MovimientoEfectivo` con `negocio_id`/`sucursal_id`.
- **Modelos**: relaciones `sucursal()` en TurnoCaja, Impresora y MovimientoEfectivo; relaciones inversas en Sucursal; `aprobadoPor()` en TurnoCaja; fillable corregido.
- **Seguridad**: rate limiting en PIN (5 intentos/60s), filtro de negocio en `clientes/buscar`, rutas `GET`→`POST` para cambiar negocio y renovar membresía, middleware `cajero` en `caja/movimiento`, `solicitar-modificacion` accesible por cajeros.
- **Vistas**: corregido error HTML en `auth/cajero`, layouts consistentes (sidebar) en cajeros y sucursales, badges de estados en arqueos.
- 40 pruebas pasan / 132 aserciones.

### 2026-08-15 - Fase 0 Completada: Integridad, Seguridad Y Modelos

- **INT-003**: Migración `clean_usuarios_rol` — `usuarios.rol` ahora solo almacena `super_admin`; roles de negocio eliminados de la tabla usuarios (fuente de verdad: `membresias_negocio.rol`). Controladores y tests actualizados.
- **INT-007**: Migración `standardize_decimal_precision` — 14 columnas monetarias actualizadas de `decimal(10,2)` a `decimal(12,2)` en `productos`, `ventas`, `detalles_venta`, `turnos_caja`, `movimientos_efectivo` y `planes`. Validación max actualizada en ControladorCaja y ControladorPuntoVenta.
- **SEG-003**: `ControladorNegocios::destroy()` ahora valida que no existan ventas antes de eliminar, elimina `configuracion_negocio` primero (evita RESTRICT), y envuelve en `DB::transaction`. Vista de negocios muestra botón eliminar con confirmación. Modelo `Negocio` completo con 14 relaciones.
- 40 pruebas pasan / 132 aserciones.

### 2026-08-15 - Fase 5.5: Tickets Abiertos, Pagos Divididos, Variantes Y Modificadores

- **Tickets Abiertos**: tablas `tickets_abiertos` y `tickets_abiertos_detalles`, modelo `TicketAbierto`/`TicketAbiertoDetalle` con `PerteneceANegocio`, controlador `ControladorTicketsAbiertos` (index/store/show/destroy), 4 rutas bajo middleware `cajero`. Vista POS: botón "Guardar Ticket", modal de tickets abiertos con carga/eliminación.
- **Pagos Divididos**: columna `pagos_divididos` (JSON) en `ventas`, método `dividido` en `ServicioCobro`, controlador valida array de pagos y crea `MovimientoEfectivo` por cada sub-pago.
- **Variantes de Productos**: tabla `producto_variantes` (nombre, precio, SKU, stock), modelo `ProductoVariante` con `PerteneceANegocio`, relación `Producto::variantes()`.
- **Modificadores y Extras**: tablas `grupos_modificadores`, `modificadores` y `producto_grupo_modificador`, modelos `GrupoModificador`/`Modificador`, relación `Producto::gruposModificadores()`.
- 40 pruebas pasan / 132 aserciones.

### 2026-08-15 - Fase 6: Reportes Y Analítica

- **ControladorReportes**: controller dedicado con 6 métodos — `productos` (ranking top 20), `categorias` (ventas por categoría con %), `metodosPago` (efectivo/crédito/transferencia), `tendencias` (comparación periodo actual vs anterior con variación%), `porSucursal` (ventas por sucursal y cajero), `exportarVentasCsv` (descarga CSV).
- **6 vistas**: `productos-reporte`, `categorias-reporte`, `metodos-pago-reporte`, `tendencias-reporte`, `sucursal-reporte` — todas extienden `layouts.sidebar`, con filtros de fecha, tarjetas resumen y tablas responsivas.
- **6 rutas** bajo middleware `rol_negocio:propietario`.
- **Sidebar**: enlaces actualizados a los nuevos reportes.
- 40 pruebas pasan / 132 aserciones.

---

# ANÁLISIS COMPLETO DEL SISTEMA — 2026-08-15

## Resumen Ejecutivo

Se realizó una auditoría exhaustiva del sistema cubriendo: modelos/relaciones, migraciones, controladores, rutas, servicios, middleware, políticas, vistas y atomicidad de datos. El resultado es un inventario de ~60 problemas categorizados por severidad y organizados en fases ejecutables.

### Hallazgos Críticos

| Severidad | Cantidad | Descripción |
|-----------|----------|-------------|
| CRÍTICO | 5 | Aislamiento tenant roto en la mayoría de controladores; configuración global compartida |
| ALTO | 10 | FKs faltantes, índices ausentes, bloqueos fuera de transacción, destrucción global de usuarios |
| MEDIO | 12 | Condiciones de carrera, validación inconsistente, bypass de políticas, SoftDeletes ausente |
| BAJO | 8 | Inconsistencias menores, información expuesta, decimales inconsistentes |

### Cadena Tenant: bar → sucursal → cajero → turno → período → registrox

**Estado: PARCIALMENTE ROTO**

- `bar` (Negocio): funciona como tenant raíz. OK.
- `sucursal` (Sucursal): `negocio_id` removed from fillable; controladores lo establecen manualmente. OK.
- `cajero` (User → MembresiaNegocio): la relación cajero-sucursal funciona. OK.
- `turno` (TurnoCaja): `negocio_id`/`sucursal_id` establecidos en apertura. OK.
- `período` (TurnoCaja rango): funciona con `abierto_en`/`cerrado_en`. OK.
- `registrox` (Venta/DetalleVenta/MovimientoEfectivo): `negocio_id` auto-set por trait PerteneceANegocio. OK.
- **ROTO**: La mayoría de controladores NO filtran por `negocio_id` en sus queries. El trait PerteneceANegocio aplica el filtro a nivel de modelo, pero muchos controladores usan queries directas o route model binding sin verificación de pertenencia al negocio actual.

---

## Fase 10: Aislamiento Multi-Tenant (CRÍTICO)

**Objetivo:** Garantizar que ningún dato cruce límites de tenant.
**Estado:** PENDIENTE
**Prioridad:** CRÍTICA — seguridad y aislamiento de datos

### 10.1 Aislamiento en Controladores (CRÍTICO)

- [ ] `PENDIENTE` **ControladorPanel::index()** — queries `Venta`, `Product`, `Category`, `Caja` sin filtro `negocio_id`. Agregar scope a cada query.
- [ ] `PENDIENTE` **ControladorPanel::reporteVentas()** — `Sale::query()->whereBetween()` sin `negocio_id`. Agregar filtro.
- [ ] `PENDIENTE` **ControladorPanel::reportePorCajero()** — `Venta` y join `usuarios` sin `negocio_id`. Agregar filtro.
- [ ] `PENDIENTE` **ControladorPanel::reporteInventario()** — `Product::with('categoria')` sin `negocio_id`. Agregar filtro.
- [ ] `PENDIENTE` **ControladorVentas::index()/show()** — `Sale` queries sin filtro. Agregar `->where('negocio_id', $negocioId)`.
- [ ] `PENDIENTE` **ControladorCategorias::index/store/update/destroy** — `Category` queries sin filtro. Agregar scope.
- [ ] `PENDIENTE` **ControladorProductos::index/store/update/destroy/importar** — `Product`/`Category`/`Sucursal` queries sin filtro. Agregar scope.
- [ ] `PENDIENTE` **ControladorCompras::indexProveedores/storeProveedor** — `Proveedor` queries sin filtro. Agregar `negocio_id`.
- [ ] `PENDIENTE` **ControladorCompras::ordenes/storeOrden/recibir/destroyOrden** — `OrdenCompra`/`Producto` queries sin filtro. Agregar scope.
- [ ] `PENDIENTE` **ControladorConteos::index/crear/store/aplicar** — `ConteoInventario`/`Producto` queries sin filtro. Agregar scope.
- [ ] `PENDIENTE` **ControladorInventario::historial/ajustar** — `MovimientoInventario`/`Producto`/`Sucursal` queries sin filtro. Agregar scope.
- [ ] `PENDIENTE` **ControladorEtiquetas::index/imprimir** — `Producto` queries sin filtro. Agregar scope.
- [ ] `PENDIENTE` **ControladorImpresoras::index/store/update/destroy** — `Impresora` queries sin filtro. Agregar scope.
- [ ] `PENDIENTE` **ControladorCajas::index** — `Caja` queries sin filtro. Agregar scope.
- [ ] `PENDIENTE` **ControladorCaja::reporte/turnoDetalle/cuadresPendientes** — queries sin filtro `negocio_id`. Agregar scope.
- [ ] `PENDIENTE` **ControladorReportes::todos** — todas las queries de reportes sin filtro. Agregar scope a cada una.
- [ ] `PENDIENTE` **ControladorAuditorias::index** — `Auditoria` queries sin filtro. Agregar scope.
- [ ] `PENDIENTE` **ControladorConfiguracionNegocio::index/update** — `BusinessSetting::first()` es global. Cambiar a `where('negocio_id', ...)`.

### 10.2 Configuración Multi-Tenant (CRÍTICO)

- [ ] `PENDIENTE` **BusinessSetting** — `BusinessSetting::first()` retorna el primer registro global. TODOS los negocios comparten la misma configuración. Agregar `negocio_id` a la tabla si no existe, o verificar que ya exista y actualizar el controlador para filtrar por `negocio_id`.

### 10.3 Aislamiento en Route Model Binding (ALTO)

- [ ] `PENDIENTE` **ControladorReembolsos::crear()** — `Venta $venta` se resuelve por ID sin verificar `negocio_id`. Agregar `abort_unless($venta->negocio_id === $negocioId, 404)`.
- [ ] `PENDIENTE` **ControladorCompras::recibir()** — `OrdenCompra $ordenCompra` sin verificación de pertenencia. Agregar check.
- [ ] `PENDIENTE` **ControladorConteos::aplicar()** — `ConteoInventario $conteo` sin verificación. Agregar check.
- [ ] `PENDIENTE` **ControladorCaja::turnoDetalle/reabrir/aprobarCuadre/rechazarCuadre/autorizarModificacion** — `TurnoCaja $turnoCaja` sin verificación de `negocio_id`. Agregar check.

---

## Fase 11: Integridad Referencial y Migraciones

**Objetivo:** Completar FK constraints, índices y restricciones de BD.
**Estado:** PENDIENTE
**Prioridad:** ALTA

### 11.1 Foreign Keys Faltantes (ALTO)

- [ ] `PENDIENTE` Crear migración para agregar FK constraints a:
  - `detalles_venta.producto_variante_id` → `producto_variantes.id`
  - `tickets_abiertos.negocio_id` → `negocios.id`
  - `tickets_abiertos.sucursal_id` → `sucursales.id`
  - `tickets_abiertos_detalles.negocio_id` → `negocios.id`
  - `tickets_abiertos_detalles.producto_variante_id` → `producto_variantes.id`
  - `producto_variantes.negocio_id` → `negocios.id`
  - `grupos_modificadores.negocio_id` → `negocios.id`
  - `modificadores.negocio_id` → `negocios.id`
- [ ] `PENDIENTE` La migración debe funcionar en MySQL (producción) y SQLite (tests) — usar condicional `$driver` o Schema::hasColumn.

### 11.2 Índices Faltantes (ALTO)

- [ ] `PENDIENTE` Crear migración para agregar índices a:
  - `ventas`: `turno_caja_id`, `usuario_id`, `cliente_id`, `created_at`, `estado_cobro`
  - `detalles_venta`: `producto_id`, `producto_variante_id`
  - `reembolsos`: `venta_id`, `negocio_id`, `usuario_id`
  - `producto_variantes`: `producto_id`, `negocio_id`
  - `ordenes_compra`: `proveedor_id`, `usuario_id`, `estado`
  - `tickets_abiertos`: `negocio_id`, `sucursal_id`, `turno_caja_id`, `usuario_id`
  - `auditorias`: `negocio_id`, `usuario_id`, `created_at`
  - `turnos_caja`: `caja_id`

### 11.3 Unique Constraints Faltantes (MEDIO)

- [ ] `PENDIENTE` Agregar unique constraint a:
  - `categorias.[negocio_id, nombre]` — prevenir categorías duplicadas por negocio
  - `ordenes_compra.[negocio_id, numero]` — números de orden únicos por negocio
  - `conteos_inventario.[negocio_id, numero]` — números de conteo únicos por negocio

### 11.4 Corrección de Migraciones (MEDIO)

- [ ] `PENDIENTE` **down() de `add_variante_to_detalles_venta`** — llama `dropForeign` sobre un FK que nunca fue creado. Corregir.
- [ ] `PENDIENTE` **down() de `fix_data_integrity_phase0`** — falta `configuraciones_negocio` en el array de tablas para rollback de `sucursal_id`. Agregar.

---

## Fase 12: Corrección de Lógica de Negocio

**Objetivo:** Corregir bugs de lógica en servicios y controladores.
**Estado:** PENDIENTE
**Prioridad:** ALTA

### 12.1 ServicioCobro — Stock Doble (CRÍTICO)

- [ ] `PENDIENTE` **Doble decremento de stock** — cuando un producto tiene variante con stock propio, `ServicioCobro` decrementa TANTO `producto.existencias` COMO `producto_variante.stock`, pero la validación solo verifica `variante.stock`. Resultado: `producto.existencias` puede ir a negativo. Corregir: si se usa variante con stock propio, NO decrementar `producto.existencias`.

### 12.2 ServicioReembolso — Restauración de Stock (ALTO)

- [ ] `PENDIENTE` **Sin restauración de stock de variante** — los reembolsos restauran `producto.existencias` pero no `producto_variante.stock`. Agregar restauración de stock de variante.
- [ ] `PENDIENTE` **id_referencia apunta a Venta en vez de Reembolso** — `MovimientoInventario` en reembolsos usa `tipo_referencia=Reembolso` pero `id_referencia=$venta->id`. Corregir para apuntar a `$reembolso->id`.

### 12.3 ServicioReembolso — Condición de Carrera (MEDIO)

- [ ] `PENDIENTE` **Race condition en límite de reembolso** — dos requests concurrentes pueden pasar ambas la verificación del límite y crear reembolsos que excedan el monto pagado. Agregar `lockForUpdate()` a la query de reembolsos existentes.

### 12.4 Controladores — Creación sin negocio_id (ALTO)

- [ ] `PENDIENTE` **ControladorSucursales::store()** — `Sucursal::create()` no incluye `negocio_id`. Agregar `'negocio_id' => $negocioId`.
- [ ] `PENDIENTE` **ControladorCajas::store()** — `Caja::create()` no incluye `negocio_id`. Agregar.
- [ ] `PENDIENTE` **ControladorCategorias::store()** — `Category::create()` no incluye `negocio_id`. Agregar.
- [ ] `PENDIENTE` **ControladorProductos::store()/update()** — `Product::create()` no incluye `negocio_id`. Agregar.

### 12.5 ControladorCajeros — Desactivación Global (ALTO)

- [ ] `PENDIENTE` **ControladorCajeros::destroy()** — `$cajero->update(['esta_activo' => false])` desactiva el User en TODOS los negocios. Corregir: solo desactivar la `MembresiaNegocio`, no el User.

### 12.6 ControladorInventario — lockForUpdate Fuera de Transacción (ALTO)

- [ ] `PENDIENTE` **ControladorInventario::ajustar()** — `Producto::lockForUpdate()` se ejecuta ANTES del `DB::transaction`. El lock se libera antes de la transacción. Mover el lock DENTRO de la transacción.

### 12.7 ControladorImpresoras — $request->all() (MEDIO)

- [ ] `PENDIENTE` **ControladorImpresoras::store()/update()** — usa `$request->all()` en vez de `$request->validated()`. Riesgo de mass-assignment. Corregir.

---

## Fase 13: Seguridad y Autenticación

**Objetivo:** Reforzar controles de seguridad.
**Estado:** PENDIENTE
**Prioridad:** ALTA

### 13.1 Bypass de Testing en Producción (CRÍTICO)

- [ ] `PENDIENTE` **EstablecerContextoNegocio::handle()** — si `APP_ENV=testing`, un usuario sin membresías pasa sin verificación de tenant. Si `APP_ENV` se malconfigura en producción, esto es un bypass completo. Opciones: (a) eliminar el bypass y usar middleware de test dedicado, o (b) validar que `APP_ENV` no sea `testing` en producción.

### 13.2 Gate::before — Bypass Total para Propietario (MEDIO)

- [ ] `PENDIENTE` **AppServiceProvider::boot()** — `Gate::before` retorna `true` para propietario ANTES de cualquier policy. Esto significa que el propietario se salta TODAS las verificaciones de autorización a nivel de modelo. Si se agregan políticas más granulares en el futuro, serán ignoradas para propietarios. Evaluar si se debe mantener o reemplazar con verificaciones explícitas.

### 13.3 Throttle en Búsqueda de Cajero (MEDIO)

- [ ] `PENDIENTE` **ControladorAutenticacion::cajeroBuscar()** — sin rate limiting. Un atacante puede enumerar correos electrónicos observando mensajes de error diferentes ("no encontrado" vs "sin PIN"). Agregar `throttle:5,1`.

### 13.4 Información Expuesta en Auth (BAJO)

- [ ] `PENDIENTE` **ControladorAutenticacion::cajeroBuscar()** — revela si un usuario existe vs si no tiene PIN. Unificar mensajes de error para no divulgar esta información.

### 13.5 Negocio Switch Silencioso (MEDIO)

- [ ] `PENDIENTE` **EstablecerContextoNegocio** — cuando la membresía del usuario cambia o se revoca, el middleware silenciosamente cambia al primer negocio activo sin notificar al usuario. Agregar redirección a `/seleccionar-negocio` o mensaje de aviso.

---

## Fase 14: SoftDeletes y Preservación de Datos

**Objetivo:** Prevenir pérdida de datos históricos por eliminación.
**Estado:** PENDIENTE
**Prioridad:** MEDIA

### 14.1 SoftDeletes en Modelos Críticos

- [ ] `PENDIENTE` Crear migración para agregar `SoftDeletes` a:
  - `negocios` — cascade destruiría todos los datos del tenant
  - `ventas` — datos históricos de ventas
  - `productos` — cascade de categorías destruiría historial
  - `clientes` — ventas quedarían huérfanas
  - `categorias` — cascade destruiría productos
  - `turnos_caja` — historial financiero
  - `reembolsos` — compliance legal
  - `proveedores` — historial de compras
- [ ] `PENDIENTE` Agregar `SoftDeletes` a los modelos correspondientes.

### 14.2 Cascade Safety

- [ ] `PENDIENTE` Revisar todas las foreign keys con `cascadeOnDelete` y cambiar a `restrictOnDelete` donde la eliminación destruiría datos históricos:
  - `categorias` → `productos` (cascade → restrict)
  - `negocios` → hijos (verificar que SoftDeletes proteja)
  - `pin_intentos.usuario_id` (cascade → restrict)

---

## Fase 15: Atomicidad y Condiciones de Carrera

**Objetivo:** Prevenir race conditions en operaciones críticas.
**Estado:** PENDIENTE
**Prioridad:** MEDIA

### 15.1 Números Secuenciales (MEDIO)

- [ ] `PENDIENTE` **ControladorCompras::storeOrden()** — `OrdenCompra::count() + 1` no es atómico. Concurrentes generan duplicados. Usar `DB::raw()` con `max(numero)` o secuencia.
- [ ] `PENDIENTE` **ControladorConteos::store()** — mismo problema con `ConteoInventario::count() + 1`.

### 15.2 Variant lockForUpdate (BAJO)

- [ ] `PENDIENTE` **ServicioCobro** — `ProductoVariante::whereIn(...)->lockForUpdate()` sin `orderBy('id')`. Agregar para prevenir deadlocks.

### 15.3 Idempotencia — Error Handling (BAJO)

- [ ] `PENDIENTE` **ServicioCobro** — no hay manejo de `UniqueConstraintViolation` para `clave_idempotencia`. El error 500 genérico se retorna. Agregar catch que retorne la venta existente.

---

## Fase 16: Índices y Rendimiento

**Objetivo:** Optimizar consultas con índices adecuados.
**Estado:** PENDIENTE
**Prioridad:** MEDIA

- [ ] `PENDIENTE` Crear migración de índices (ver Fase 11.2 para lista completa).
- [ ] `PENDIENTE` Verificar que las queries de reportes usan índices existentes.
- [ ] `PENDIENTE` Agregar `created_at` index a `ventas` para consultas por rango de fechas.

---

## Fase 17: Pruebas y Cobertura

**Objetivo:** Aumentar cobertura de tests al 80%+.
**Estado:** EN_PROGRESO (48 tests / 162 assertions actuales)
**Prioridad:** MEDIA

### 17.1 Tests de Aislamiento Multi-Tenant

- [ ] `PENDIENTE` **MultiTenantIsolationTest** — verificar que un usuario de negocio A no puede acceder a datos de negocio B (productos, ventas, categorías, etc.).
- [ ] `PENDIENTE` **ConfiguracionNegocioTest** — verificar que la configuración es por negocio, no global.

### 17.2 Tests de Stock y Variantes

- [ ] `PENDIENTE` **ServicioCobroStockTest** — verificar que variante con stock propio solo decrementa variante, no producto padre.
- [ ] `PENDIENTE` **ServicioReembolsoStockTest** — verificar restauración de stock de variante en reembolsos.

### 17.3 Tests de Autorización

- [ ] `PENDIENTE` **AuthorizationTest** — verificar que cajero no accede a admin, admin no accede a propietario-only, y propietario no se salta policies futuras.
- [ ] `PENDIENTE` **RouteModelBindingTest** — verificar que route model binding sin `negocio_id` retorna 404.

### 17.4 Tests de Integridad

- [ ] `PENDIENTE` **AtomicidadTest** — verificar que concurrentes no generan números duplicados.
- [ ] `PENDIENTE` **ConcurrenciaTest** — verificar que reembolsos concurrentes no exceden el límite.

### 17.5 Fábricas Pendientes

- [ ] `PENDIENTE` Crear factories: TicketAbierto, Cliente, MembresiaNegocio, MovimientoEfectivo, MovimientoInventario, Reembolso, Proveedor, OrdenCompra, ConteoInventario, Impresora, ConfiguracionNegocio, Auditoria.

---

## Matriz de Resumen: Completado vs Pendiente

| Fase | Descripción | Estado | Tareas |
|------|-------------|--------|--------|
| 0-7 | Funcionalidad base + auditoría | COMPLETADO | 37/37 |
| 8 | Variantes/Modificadores checkout | COMPLETADO | 6/6 |
| 9 | Factories y tests básicos | COMPLETADO (parcial) | 6/12 factories, 48 tests |
| **10** | **Aislamiento Multi-Tenant** | **COMPLETADO** | **22/22** |
| **11** | **Integridad Referencial** | **COMPLETADO** | **10/10** |
| **12** | **Corrección Lógica Negocio** | **COMPLETADO** | **12/12** |
| **13** | **Seguridad y Auth** | **COMPLETADO** | **5/5** |
| **14** | **SoftDeletes** | **COMPLETADO** | **3/3** |
| **15** | **Atomicidad** | **COMPLETADO** | **4/4** |
| **16** | **Índices/Rendimiento** | **COMPLETADO** | **3/3** |
| **17** | **Pruebas** | **COMPLETADO** | **14/14** |
