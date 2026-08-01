# Plan De Desarrollo TPV Ecuador

## Objetivo

Convertir el TPV en un sistema seguro, multi-tenant y orientado a dispositivos móviles, tomando como referencia funcional las capacidades públicas de Loyverse sin copiar su identidad visual ni sus recursos propietarios.

## Reglas De Trabajo

- Cada funcionalidad se implementará como una unidad atómica.
- Antes de editar se revisarán modelo, rutas, vistas, permisos y dependencias.
- Cada unidad debe incluir migración, lógica, interfaz y pruebas cuando corresponda.
- Las ventas, existencias, cajas y permisos deben validarse en el servidor.
- No se registrarán clientes específicos; las ventas usarán consumidor genérico.
- No se ejecutará `migrate:fresh` en producción.
- Las migraciones productivas usarán `php artisan migrate --force`.
- Después de cada unidad se actualizará este archivo.
- Un cambio llega a producción únicamente mediante merge hacia `prod`.

## Estados

- `COMPLETADO`: implementado y validado.
- `EN_PROGRESO`: se está implementando.
- `PENDIENTE`: planificado, todavía no iniciado.
- `BLOQUEADO`: requiere una decisión, servicio externo o dato pendiente.

## Estado Actual

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

### Operación De Caja

- [x] `COMPLETADO` Caja activa por defecto.
- [x] `COMPLETADO` Apertura de turno con fondo inicial.
- [x] `COMPLETADO` Cierre de turno con efectivo contado.
- [x] `COMPLETADO` Cálculo de efectivo esperado y diferencia.
- [x] `COMPLETADO` Asociación de venta con usuario y turno.
- [ ] `PENDIENTE` Entradas y retiros manuales de efectivo.
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

## Fase 1: Multi-Tenant

### 1.1 Negocios

- [ ] Crear tabla `negocios`.
- [ ] Crear modelo `Negocio`.
- [ ] Crear CRUD administrativo de negocios.
- [ ] Definir identificador único del negocio.
- [ ] Definir zona horaria, moneda y configuración por negocio.

### 1.2 Sucursales

- [ ] Crear tabla `sucursales` con `negocio_id`.
- [ ] Crear modelo `Sucursal`.
- [ ] Crear CRUD de sucursales.
- [ ] Asociar cajas, impresoras, productos y ventas a sucursales.

### 1.3 Membresías

- [ ] Crear tabla `membresias_negocio`.
- [ ] Permitir que un usuario pertenezca a uno o varios negocios.
- [ ] Crear selector de negocio y sucursal después del login.
- [ ] Crear `ContextoNegocio` para negocio, sucursal y usuario actuales.

### 1.4 Aislamiento

- [ ] Crear middleware de tenant.
- [ ] Aplicar scopes obligatorios por `negocio_id`.
- [ ] Validar relaciones dentro del negocio actual.
- [ ] Crear índices compuestos de aislamiento.
- [ ] Crear pruebas de lectura cruzada entre negocios.

## Fase 2: Cajeros Y Permisos

- [ ] Definir roles: administrador, gerente, supervisor y cajero.
- [ ] Crear permisos por módulo y acción.
- [ ] Aplicar policies a productos, ventas, cajas, reportes y configuración.
- [ ] Implementar PIN de acceso rápido al POS.
- [ ] Registrar usuario responsable en ventas y movimientos.
- [ ] Crear auditoría de descuentos, devoluciones, ajustes y reaperturas.
- [ ] Crear reportes de ventas por cajero.

## Fase 3: Efectivo Y Arqueos

- [ ] Crear `movimientos_efectivo`.
- [ ] Registrar ventas en efectivo.
- [ ] Registrar retiros, gastos y entradas.
- [ ] Exigir motivo en retiros y ajustes.
- [ ] Mejorar cálculo de efectivo esperado.
- [ ] Crear reporte de turno.
- [ ] Crear reporte de diferencias por usuario, caja y sucursal.

## Fase 4: Inventario Avanzado

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

La siguiente unidad recomendada es **movimientos manuales de efectivo**, porque completa la apertura, manejo y cierre de caja antes de continuar con multi-tenant.

## Registro De Implementaciones

### 2026-08-01

- Se creó este plan.
- Se documentó el estado funcional actual.
- Se documentó la estrategia multi-tenant.
- Se documentó el flujo de caja y cajeros.
- Se excluyó deliberadamente el módulo de clientes específicos.
