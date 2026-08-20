<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ConteoInventario;
use App\Models\DetalleConteo;
use App\Models\DetalleOrdenCompra;
use App\Models\DetalleVenta;
use App\Models\Impresora;
use App\Models\MembresiaNegocio;
use App\Models\MovimientoEfectivo;
use App\Models\MovimientoInventario;
use App\Models\OrdenCompra;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Reembolso;
use App\Models\TicketAbierto;
use App\Models\TicketAbiertoDetalle;
use App\Models\TurnoCajero;
use App\Models\Venta;

class GuardiaEliminacion
{
    /**
     * Dependencias de un producto que impiden su eliminación.
     *
     * @return list<string>
     */
    public static function productoConDependencias(int $productoId): array
    {
        return static::colector([
            [DetalleVenta::class, 'ventas'],
            [TicketAbiertoDetalle::class, 'tickets abiertos'],
            [DetalleOrdenCompra::class, 'órdenes de compra'],
            [DetalleConteo::class, 'conteos de inventario'],
            [MovimientoInventario::class, 'movimientos de inventario'],
        ], static fn (string $modelo) => $modelo::where('producto_id', $productoId)->exists());
    }

    /**
     * Dependencias de una sucursal que impiden su eliminación.
     *
     * @return list<string>
     */
    public static function sucursalConDependencias(int $sucursalId): array
    {
        return static::colector([
            [TurnoCajero::class, 'turnos de cajero'],
            [Venta::class, 'ventas'],
            [MovimientoEfectivo::class, 'movimientos de efectivo'],
            [TicketAbierto::class, 'tickets abiertos'],
            [Reembolso::class, 'reembolsos'],
            [MovimientoInventario::class, 'movimientos de inventario'],
            [ConteoInventario::class, 'conteos de inventario'],
            [Categoria::class, 'categorías'],
            [Cliente::class, 'clientes'],
            [Impresora::class, 'impresoras'],
            [MembresiaNegocio::class, 'cajeros o administradores asignados'],
        ], static fn (string $modelo) => $modelo::withoutGlobalScope('negocio')->where('sucursal_id', $sucursalId)->exists());
    }

    /**
     * Dependencias de una categoría que impiden su eliminación.
     *
     * @return list<string>
     */
    public static function categoriaConDependencias(int $categoriaId): array
    {
        return static::colector([
            [Producto::class, 'productos asociados'],
        ], static fn (string $modelo) => $modelo::where('categoria_id', $categoriaId)->exists());
    }

    /**
     * Dependencias de un proveedor que impiden su eliminación.
     *
     * @return list<string>
     */
    public static function proveedorConDependencias(int $proveedorId): array
    {
        return static::colector([
            [OrdenCompra::class, 'órdenes de compra'],
        ], static fn (string $modelo) => $modelo::where('proveedor_id', $proveedorId)->exists());
    }

    /**
     * Dependencias de un rol que impiden su eliminación.
     *
     * @return list<string>
     */
    public static function rolConDependencias(int $rolId): array
    {
        return static::colector([
            [MembresiaNegocio::class, 'cajeros o administradores asignados'],
        ], static function (string $modelo) use ($rolId) {
            return $modelo::where('rol_id', $rolId)->where('esta_activa', true)->exists();
        });
    }

    /**
     * Dependencias de un negocio (bar) que impiden su eliminación.
     *
     * @return list<string>
     */
    public static function negocioConDependencias(int $negocioId): array
    {
        return static::colector([
            [Venta::class, 'ventas'],
        ], static fn (string $modelo) => $modelo::withoutGlobalScope('negocio')->where('negocio_id', $negocioId)->exists());
    }

    /**
     * Dependencias de un contrato que impiden su eliminación.
     *
     * @return list<string>
     */
    public static function contratoConDependencias(int $contratoId): array
    {
        return static::colector([
            [Pago::class, 'pagos registrados'],
        ], static fn (string $modelo) => $modelo::where('contrato_id', $contratoId)->where('estado', 'registrado')->exists());
    }

    /**
     * @param  list<array{0: string, 1: string}>  $chequeos
     * @return list<string>
     */
    private static function colector(array $chequeos, callable $existe): array
    {
        $dependencias = [];

        foreach ($chequeos as [$modelo, $nombre]) {
            if ($existe($modelo)) {
                $dependencias[] = $nombre;
            }
        }

        return $dependencias;
    }
}