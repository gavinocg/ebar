<?php

namespace App\Services;

use App\Models\ConfiguracionNegocio as BusinessSetting;
use App\Models\MovimientoInventario as InventoryMovement;
use App\Models\Producto as Product;
use App\Models\ProductoVariante;
use App\Models\Venta as Sale;
use App\Models\TurnoCaja;
use App\Models\MovimientoEfectivo;
use App\Models\Cliente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServicioCobro
{
    public function crear(array $itemsData, string $paymentMethod, string $paid, ?string $notes, string $idempotencyKey, ?int $clienteId = null, ?string $descripcionCliente = null, ?string $entidadFinanciera = null, ?string $numeroComprobantePago = null, ?float $descuentoPorcentaje = null, ?array $pagosDivididos = null): Sale
    {
        return DB::transaction(function () use ($itemsData, $paymentMethod, $paid, $notes, $idempotencyKey, $clienteId, $descripcionCliente, $entidadFinanciera, $numeroComprobantePago, $descuentoPorcentaje, $pagosDivididos) {
            $existingSale = Sale::where('clave_idempotencia', $idempotencyKey)->first();

            if ($existingSale) {
                return $existingSale->load('detalles');
            }

            $turnoCaja = TurnoCaja::where('usuario_id', Auth::id())
                ->where('estado', 'abierta')
                ->latest('id')
                ->first();

            if (!$turnoCaja) {
                throw new \RuntimeException('Debes abrir un turno de caja antes de registrar ventas.');
            }

            $quantities = collect($itemsData)
                ->groupBy(fn ($item) => ($item['producto_id'] ?? 0) . '-' . ($item['variante_id'] ?? 'base'))
                ->map(fn ($items) => $items->sum('cantidad'));
            $productIds = collect($itemsData)->pluck('producto_id')->unique()->all();
            $products = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $varianteIds = collect($itemsData)->pluck('variante_id')->filter()->unique()->all();
            $variantes = $varianteIds
                ? ProductoVariante::whereIn('id', $varianteIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id')
                : collect();

            $business = BusinessSetting::obtenerConfiguracion();
            $descuentoActivo = (bool) ($business->descuento_activo ?? false);
            $cliente = $paymentMethod === 'credito'
                ? Cliente::where('esta_activo', true)->findOrFail($clienteId)
                : null;
            $subtotal = 0;
            $totalDescuentoProductos = 0;
            $saleItems = [];

            foreach ($itemsData as $itemData) {
                $productId = $itemData['producto_id'];
                $quantity = $itemData['cantidad'];
                $varianteId = $itemData['variante_id'] ?? null;
                $modificadoresData = $itemData['modificadores'] ?? [];

                $product = $products->get($productId);

                if (!$product || !$product->esta_activo) {
                    throw new \RuntimeException('Uno de los productos ya no está disponible.');
                }

                $unitPrice = (float) $product->precio;
                if ($varianteId && isset($variantes[$varianteId])) {
                    $variante = $variantes[$varianteId];
                    $unitPrice = (float) $variante->precio;
                    if ($variante->stock !== null && $product->maneja_existencias && $variante->stock < $quantity) {
                        throw new \RuntimeException("Existencias insuficientes para la variante {$variante->nombre}.");
                    }
                } elseif ($product->maneja_existencias && $product->existencias < $quantity) {
                    throw new \RuntimeException("Existencias insuficientes para {$product->nombre}.");
                }

                $modificadoresTotal = 0;
                foreach ($modificadoresData as $mod) {
                    $modificadoresTotal += (float) ($mod['precio_extra'] ?? 0);
                }

                $itemSubtotal = round(($unitPrice + $modificadoresTotal) * $quantity, 2);
                $itemDescuento = 0;
                if ($descuentoActivo && (float) ($product->descuento ?? 0) > 0) {
                    $itemDescuento = round($itemSubtotal * ((float) $product->descuento / 100), 2);
                }
                $itemNeto = round($itemSubtotal - $itemDescuento, 2);
                $subtotal = round($subtotal + $itemSubtotal, 2);
                $totalDescuentoProductos = round($totalDescuentoProductos + $itemDescuento, 2);
                $saleItems[] = [
                    'producto_id' => $product->id,
                    'producto_variante_id' => $varianteId,
                    'nombre_producto' => $product->nombre . ($varianteId && isset($variantes[$varianteId]) ? ' - ' . $variantes[$varianteId]->nombre : ''),
                    'cantidad' => $quantity,
                    'precio' => $unitPrice + $modificadoresTotal,
                    'descuento' => $itemDescuento,
                    'subtotal' => $itemNeto,
                    'modificadores' => $modificadoresData,
                ];
            }

            $taxEnabled = (bool) $business->cobrar_impuesto;
            $taxPercentage = $taxEnabled ? (float) $business->porcentaje_impuesto : 0;
            $subtotalConDescuento = round($subtotal - $totalDescuentoProductos, 2);
            $tax = round($subtotalConDescuento * ($taxPercentage / 100), 2);
            $total = round($subtotalConDescuento + $tax, 2);
            $paidAmount = $paymentMethod === 'credito' ? 0 : round((float) $paid, 2);
            $cambio = in_array($paymentMethod, ['credito', 'dividido']) ? 0 : round($paidAmount - $total, 2);

            if ($paymentMethod !== 'credito' && $paymentMethod !== 'dividido' && $paidAmount < $total) {
                throw new \RuntimeException('El monto recibido es insuficiente.');
            }

            if ($paymentMethod === 'dividido' && $pagosDivididos) {
                $sumaPagos = array_sum(array_map(fn($p) => (float) $p['monto'], $pagosDivididos));
                if (round($sumaPagos, 2) < round($total, 2)) {
                    throw new \RuntimeException('La suma de los pagos divididos es insuficiente. Total: $' . number_format($total, 2) . ', Pagado: $' . number_format($sumaPagos, 2));
                }
                $paidAmount = round($sumaPagos, 2);
                $cambio = round($paidAmount - $total, 2);
            }

            $descuentoTotal = $totalDescuentoProductos;
            $descuentoPorcentajeFinal = $descuentoActivo && $descuentoPorcentaje > 0 ? $descuentoPorcentaje : null;

            $sale = Sale::create([
                'sucursal_id' => $turnoCaja->sucursal_id,
                'numero_comprobante' => 'PENDING-' . Str::uuid(),
                'clave_idempotencia' => $idempotencyKey,
                'turno_caja_id' => $turnoCaja->id,
                'usuario_id' => Auth::id(),
                'subtotal' => $subtotal,
                'descuento' => $descuentoTotal,
                'descuento_porcentaje' => $descuentoPorcentajeFinal,
                'impuesto' => $tax,
                'impuesto_habilitado' => $taxEnabled,
                'porcentaje_impuesto' => $taxPercentage,
                'total' => $total,
                'metodo_pago' => $paymentMethod,
                'pagado' => $paidAmount,
                'cambio' => $cambio,
                'notas' => $notes,
                'cliente_id' => $cliente?->id,
                'nombre_cliente' => $cliente?->nombre,
                'descripcion_cliente' => $descripcionCliente,
                'entidad_financiera' => $entidadFinanciera,
                'numero_comprobante_pago' => $numeroComprobantePago,
                'pagos_divididos' => $pagosDivididos,
                'estado_cobro' => $paymentMethod === 'credito' ? 'pendiente' : 'pagado',
            ]);

            $sale->update([
                'numero_comprobante' => 'CMP-' . str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
            ]);

            foreach ($saleItems as $item) {
                $modificadoresData = $item['modificadores'] ?? [];
                unset($item['modificadores']);

                $detalle = $sale->detalles()->create($item);

                if (!empty($modificadoresData)) {
                    $modIds = array_column($modificadoresData, 'modificador_id');
                    foreach ($modificadoresData as $mod) {
                        $detalle->modificadores()->attach($mod['modificador_id'], [
                            'precio_extra' => $mod['precio_extra'] ?? 0,
                        ]);
                    }
                }

                $product = $products[$item['producto_id']];
                $hasVariantWithStock = !empty($item['producto_variante_id']) 
                    && isset($variantes[$item['producto_variante_id']]) 
                    && $variantes[$item['producto_variante_id']]->stock !== null;

                if ($product->maneja_existencias && !$hasVariantWithStock) {
                    $stockBefore = $product->existencias;
                    $product->decrement('existencias', $item['cantidad']);

                    InventoryMovement::create([
                        'producto_id' => $product->id,
                        'usuario_id' => Auth::id(),
                        'tipo' => 'venta',
                        'cantidad' => -$item['cantidad'],
                        'existencias_anteriores' => $stockBefore,
                        'existencias_posteriores' => $stockBefore - $item['cantidad'],
                        'tipo_referencia' => Sale::class,
                        'id_referencia' => $sale->id,
                    ]);
                }

                if ($hasVariantWithStock) {
                    $v = $variantes[$item['producto_variante_id']];
                    $v->decrement('stock', $item['cantidad']);
                }
            }

            if ($paymentMethod === 'efectivo') {
                MovimientoEfectivo::create([
                    'negocio_id' => $turnoCaja->negocio_id,
                    'sucursal_id' => $turnoCaja->sucursal_id,
                    'caja_id' => $turnoCaja->caja_id,
                    'turno_caja_id' => $turnoCaja->id,
                    'usuario_id' => Auth::id(),
                    'tipo' => 'venta',
                    'monto' => $paidAmount,
                    'motivo' => 'Venta ' . $sale->numero_comprobante,
                    'tipo_referencia' => Sale::class,
                    'id_referencia' => $sale->id,
                ]);
            } elseif ($paymentMethod === 'transferencia') {
                MovimientoEfectivo::create([
                    'negocio_id' => $turnoCaja->negocio_id,
                    'sucursal_id' => $turnoCaja->sucursal_id,
                    'caja_id' => $turnoCaja->caja_id,
                    'turno_caja_id' => $turnoCaja->id,
                    'usuario_id' => Auth::id(),
                    'tipo' => 'transferencia',
                    'monto' => $paidAmount,
                    'motivo' => 'Transferencia ' . $sale->numero_comprobante . ($entidadFinanciera ? ' - ' . $entidadFinanciera : ''),
                    'tipo_referencia' => Sale::class,
                    'id_referencia' => $sale->id,
                ]);
            } elseif ($paymentMethod === 'dividido' && $pagosDivididos) {
                foreach ($pagosDivididos as $pago) {
                    $tipoMovimiento = $pago['metodo'] === 'transferencia' ? 'transferencia' : 'venta';
                    $motivo = ($pago['metodo'] === 'transferencia' ? 'Transferencia ' : 'Venta ') . $sale->numero_comprobante;
                    MovimientoEfectivo::create([
                        'negocio_id' => $turnoCaja->negocio_id,
                        'sucursal_id' => $turnoCaja->sucursal_id,
                        'caja_id' => $turnoCaja->caja_id,
                        'turno_caja_id' => $turnoCaja->id,
                        'usuario_id' => Auth::id(),
                        'tipo' => $tipoMovimiento,
                        'monto' => round((float) $pago['monto'], 2),
                        'motivo' => $motivo,
                        'tipo_referencia' => Sale::class,
                        'id_referencia' => $sale->id,
                    ]);
                }
            }

            return $sale->load('detalles');
        });
    }
}
