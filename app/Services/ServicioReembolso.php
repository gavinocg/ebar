<?php

namespace App\Services;

use App\Models\MovimientoEfectivo;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Reembolso;
use App\Models\ReembolsoDetalle;
use App\Models\Venta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServicioReembolso
{
    public function crear(Venta $venta, string $tipo, array $items, string $motivo, string $metodo = 'efectivo', ?int $autorizadoPor = null): Reembolso
    {
        return DB::transaction(function () use ($venta, $tipo, $items, $motivo, $metodo, $autorizadoPor) {
            $venta->load('detalles.producto');

            if ($tipo === 'total' && $venta->reembolsos()->where('tipo', 'total')->exists()) {
                throw new \RuntimeException('La venta ya tiene un reembolso total registrado.');
            }

            $totalDetalles = $venta->detalles->count();
            $itemsRecibidos = count($items);
            if ($tipo === 'total' && $itemsRecibidos < $totalDetalles) {
                throw new \RuntimeException('Un reembolso total debe incluir todos los artículos de la venta.');
            }

            $reembolsoDetalles = [];
            $montoTotal = 0;

            $productoIds = array_unique(array_map(fn ($detalle) => $venta->detalles->firstWhere('id', $detalle)->producto_id ?? null, array_keys($items)));
            $productoIds = array_filter($productoIds);
            $productosLock = Producto::whereIn('id', $productoIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $detalleVentaId => $cantidad) {
                $detalle = $venta->detalles->firstWhere('id', $detalleVentaId);

                if (!$detalle) {
                    throw new \RuntimeException('Uno de los artículos no pertenece a la venta.');
                }

                $devueltoAntes = ReembolsoDetalle::where('detalle_venta_id', $detalle->id)->sum('cantidad');
                $disponible = $detalle->cantidad - $devueltoAntes;

                if ($cantidad < 1 || $cantidad > $disponible) {
                    throw new \RuntimeException("Cantidad no válida para {$detalle->nombre_producto} (disponible: {$disponible}).");
                }

                $precioUnitario = (float) $detalle->precio;
                $descuentoDetalle = (float) ($detalle->descuento ?? 0);
                $descuentoUnitario = $detalle->cantidad > 0 ? round($descuentoDetalle / $detalle->cantidad, 2) : 0;
                $montoUnitarioNeto = round($precioUnitario - $descuentoUnitario, 2);
                $montoDetalle = round($montoUnitarioNeto * $cantidad, 2);
                $montoTotal = round($montoTotal + $montoDetalle, 2);

                $reembolsoDetalles[] = [
                    'detalle_venta_id' => $detalle->id,
                    'cantidad' => $cantidad,
                    'monto' => $montoDetalle,
                ];

                if ($detalle->producto && $detalle->producto->maneja_existencias) {
                    $hasVariantWithStock = $detalle->producto_variante_id 
                        && $detalle->variante 
                        && $detalle->variante->stock !== null;

                    if (!$hasVariantWithStock) {
                        $producto = $productosLock->get($detalle->producto_id);
                        if ($producto) {
                            $stockAntes = $producto->existencias;
                            $producto->increment('existencias', $cantidad);

                            // TODO: id_referencia points to Venta, not Reembolso (reembolso doesn't exist yet at this point)
                            MovimientoInventario::create([
                                'producto_id' => $producto->id,
                                'usuario_id' => Auth::id(),
                                'tipo' => 'devolucion',
                                'cantidad' => $cantidad,
                                'existencias_anteriores' => $stockAntes,
                                'existencias_posteriores' => $stockAntes + $cantidad,
                                'tipo_referencia' => Reembolso::class,
                                'id_referencia' => $venta->id,
                                'notas' => 'Devolución de ' . $venta->numero_comprobante,
                            ]);
                        }
                    } elseif ($detalle->variante) {
                        $v = $detalle->variante;
                        $stockAntes = $v->stock;
                        $v->increment('stock', $cantidad);

                        MovimientoInventario::create([
                            'producto_id' => $detalle->producto_id,
                            'usuario_id' => Auth::id(),
                            'tipo' => 'devolucion',
                            'cantidad' => $cantidad,
                            'existencias_anteriores' => $stockAntes,
                            'existencias_posteriores' => $stockAntes + $cantidad,
                            'tipo_referencia' => Reembolso::class,
                            'id_referencia' => $venta->id,
                            'notas' => 'Devolución variante ' . ($v->nombre ?? '') . ' de ' . $venta->numero_comprobante,
                        ]);
                    }
                }
            }

            $montoTotalReembolsado = (float) Reembolso::where('venta_id', $venta->id)->sum('monto');
            $montoDisponible = (float) $venta->pagado - $montoTotalReembolsado;
            if ($montoTotal > $montoDisponible) {
                throw new \RuntimeException('El monto del reembolso excede lo pagado. Disponible: $' . number_format($montoDisponible, 2));
            }

            $reembolso = Reembolso::create([
                'negocio_id' => $venta->negocio_id,
                'sucursal_id' => $venta->sucursal_id,
                'venta_id' => $venta->id,
                'usuario_id' => Auth::id(),
                'tipo' => $tipo,
                'monto' => $montoTotal,
                'motivo' => $motivo,
                'metodo' => $metodo,
                'autorizado_por' => $autorizadoPor,
            ]);

            foreach ($reembolsoDetalles as $detalle) {
                ReembolsoDetalle::create(array_merge($detalle, ['reembolso_id' => $reembolso->id]));
            }

            if ($metodo === 'efectivo' && $venta->turnoCaja && $venta->turnoCaja->estado === 'abierta') {
                MovimientoEfectivo::create([
                    'negocio_id' => $venta->negocio_id,
                    'sucursal_id' => $venta->sucursal_id,
                    'caja_id' => $venta->turnoCaja->caja_id,
                    'turno_caja_id' => $venta->turnoCaja->id,
                    'usuario_id' => Auth::id(),
                    'tipo' => 'retiro',
                    'monto' => $montoTotal,
                    'motivo' => 'Reembolso ' . $venta->numero_comprobante,
                    'tipo_referencia' => Reembolso::class,
                    'id_referencia' => $reembolso->id,
                ]);
            }

            return $reembolso->load('detalles');
        });
    }
}