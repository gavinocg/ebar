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
- [ ] `PENDIENTE` Reapertura autorizada de turnos cerrados.
- [ ] `PENDIENTE` Reporte de arqueos y diferencias por cajero.

### Despliegue

- [x] `COMPLETADO` Ramas `dev` y `prod` publicadas.
- [x] `COMPLETADO` Aplicación desplegada en `192.168.100.101`.
- [x] `COMPLETADO` Servicio Laravel persistente en `127.0.0.1:8001`.
- [x] `COMPLETADO` Base MySQL creada y migrada en el servidor.
- [x] `COMPLETADO` Sincronizador `ebar-sync.timer` para revisar `origin/prod`.
- [x] `COMPLETADO` Cloudflare Tunnel configurado hacia `http://localhost:8001`.
- [ ] `PENDIENTE` Rotar el token de Cloudflare compartido durante la configuración.

## Fase 1: Multi-Tenant `EN_PROGRESO`

### 1.1 Negocios

- [x] `COMPLETADO` Crear tabla `negocios`.
- [x] `COMPLETADO` Crear modelo `Negocio`.
- [ ] Crear CRUD administrativo de negocios para `super_admin`.
- [ ] Definir identificador único del negocio.
- [ ] Definir zona horaria, moneda y configuración por negocio.
- [ ] Asociar administrador principal del bar.
- [ ] Definir estados: prueba, activo, suspendido, vencido y cancelado.

### 1.2 Sucursales

- [x] `COMPLETADO` Crear tabla `sucursales` con `negocio_id`.
- [x] `COMPLETADO` Crear modelo `Sucursal`.
- [ ] Crear CRUD de sucursales.
- [ ] Asociar cajas, impresoras, productos y ventas a sucursales.

### 1.3 Membresías

- [x] `COMPLETADO` Crear tabla `membresias_negocio`.
- [x] `COMPLETADO` Permitir que un usuario pertenezca a uno o varios negocios.
- [ ] Crear selector de negocio y sucursal después del login.
- [x] `COMPLETADO` Crear `ContextoNegocio` para negocio, sucursal y usuario actuales.

### 1.4 Aislamiento

- [x] `COMPLETADO` Crear middleware de tenant.
- [x] `COMPLETADO` Aplicar scopes obligatorios por `negocio_id`.
- [x] `COMPLETADO` Validar relaciones dentro del negocio actual.
- [x] `COMPLETADO` Crear índices compuestos de aislamiento.
- [x] `COMPLETADO` Crear pruebas de lectura cruzada entre negocios.

### 1.5 Membresías Y Super Administrador

- [ ] Crear planes de membresía.
- [ ] Crear fechas de inicio, vencimiento y renovación.
- [ ] Crear límites por plan para cajeros, cajas, sucursales y almacenamiento.
- [ ] Crear rol global `super_admin` fuera del tenant.
- [ ] Crear panel global de bares.
- [ ] Crear alta de bar y administrador inicial.
- [ ] Activar, suspender y reactivar bares.
- [ ] Bloquear bares vencidos o suspendidos.

## Fase 2: Cajeros Y Permisos

- [ ] Definir roles por bar: `admin_bar` y `cajero`.
- [ ] Permitir que `admin_bar` opere también como cajero.
- [ ] Permitir registrar una cantidad de cajeros limitada por la membresía.
- [ ] Desactivar cajeros sin borrar su historial.
- [ ] Implementar PIN numérico de 4 dígitos.
- [ ] Crear acceso de cajero exclusivo para POS.
- [ ] Impedir que un cajero acceda al backoffice.
- [ ] Permitir a `admin_bar` administrar sus cajeros.
- [ ] Crear permisos por módulo y acción.
- [ ] Aplicar policies a productos, ventas, cajas, reportes y configuración.
- [ ] Implementar PIN de acceso rápido al POS.
- [ ] Registrar usuario responsable en ventas y movimientos.
- [ ] Crear auditoría de descuentos, devoluciones, ajustes y reaperturas.
- [ ] Crear reportes de ventas por cajero.

## Fase 3: Efectivo Y Arqueos

- [x] Crear `movimientos_efectivo`.
- [x] Registrar ventas en efectivo.
- [x] Registrar retiros, gastos y entradas.
- [x] Exigir motivo en retiros y ajustes.
- [ ] Mejorar cálculo de efectivo esperado.
- [ ] Crear reporte de turno.
- [ ] Crear reporte de diferencias por usuario, caja y sucursal.

## Fase 4: Inventario Avanzado

- [x] Permitir productos con existencias controladas o disponibilidad indefinida.
- [ ] Crear ajustes manuales auditados.
- [ ] Crear pantalla de historial de inventario.
- [ ] Agregar motivo, usuario y referencia a cada ajuste.
- [ ] Crear niveles mínimos configurables por producto.
- [ ] Crear alertas de existencias bajas.
- [ ] Separar catálogo de existencias por sucursal.
- [ ] Crear proveedores y órdenes de compra.
- [ ] Crear recepción de mercancía.
- [ ] Crear conteos físicos.
- [ ] Crear importación y exportación CSV.
- [ ] Crear impresión de etiquetas.

## Fase 5: Ventas Avanzadas

- [ ] Crear descuentos por producto.
- [ ] Crear descuentos por comprobante.
- [ ] Congelar descuentos en el detalle de venta.
- [ ] Crear reembolsos parciales y totales.
- [ ] Revertir existencias mediante movimientos compensatorios.
- [ ] Exigir autorización para anulaciones y devoluciones.
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

La siguiente fase es **flujo operativo de cajero y cobro**, comenzando por acceso con cédula/PIN y apertura obligatoria de caja.

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
