<?php

namespace App\Services;

use App\Models\ConfiguracionNegocio as BusinessSetting;
use App\Models\Modificador;
use App\Models\MovimientoInventario as InventoryMovement;
use App\Models\Producto as Product;
use App\Models\ProductoVariante;
use App\Models\Venta as Sale;
use App\Models\TurnoCajero;
use App\Models\MovimientoEfectivo;
use App\Models\Cliente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServicioCobro
{
    public function crear(array $itemsData, string $paymentMethod, string $paid, ?string $notes, string $idempotencyKey, ?int $clienteId = null, ?string $descripcionCliente = null, ?string $entidadFinanciera = null, ?string $numeroComprobantePago = null, ?float $descuentoPorcentaje = null, ?array $pagosDivididos = null): Sale
    {
        return DB::transaction(function () use ($itemsData, $paymentMethod, $paid, $notes, $idempotencyKey, $clienteId, $descripcionCliente, $entidadFinanciera, $numeroComprobantePago, $descuentoPorcentaje, $pagosDivididos) {
            $existingSale = Sale::where('clave_idempotencia', $idempotencyKey)
                ->where('usuario_id', Auth::id())
                ->first();

            if ($existingSale) {
                return $existingSale->load('detalles');
            }

            $turnoCajero = TurnoCajero::where('usuario_id', Auth::id())
                ->where('estado', 'abierta')
                ->latest('id')
                ->first();

            if (!$turnoCajero) {
                throw new \RuntimeException('Debes abrir un turno de cajero antes de registrar ventas.');
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

            $modificadorIds = collect($itemsData)
                ->flatMap(fn ($item) => collect($item['modificadores'] ?? [])->pluck('modificador_id'))
                ->unique()
                ->all();
            $modificadores = $modificadorIds
                ? Modificador::whereIn('id', $modificadorIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id')
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
                    throw new \RuntimeException('Uno de los productos ya no estÃ¡ disponible.');
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
                $modificadoresFinales = [];
                foreach ($modificadoresData as $mod) {
                    $modificador = $modificadores->get($mod['modificador_id'] ?? null);

                    if (!$modificador || !$modificador->esta_activo) {
                        throw new \RuntimeException('Uno de los modificadores ya no estÃ¡ disponible.');
                    }

                    $precioExtra = (float) $modificador->precio_extra;
                    $modificadoresTotal += $precioExtra;
                    $modificadoresFinales[] = [
                        'modificador_id' => $modificador->id,
                        'precio_extra' => $precioExtra,
                    ];
                }

                $itemSubtotal = round(($unitPrice + $modificadoresTotal) * $quantity, 2);
                $itemDescuento = 0;
                if ($descuentoActivo && (float) ($product->descuento ?? 0) > 0) {
                    $descuentoCalculado = $itemSubtotal * ((float) $product->descuento / 100);
                    $itemDescuento = round(min($itemSubtotal, $descuentoCalculado), 2);
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
                    'modificadores' => $modificadoresFinales,
                ];
            }

            $taxEnabled = (bool) $business->cobrar_impuesto;
            $taxPercentage = $taxEnabled ? (float) $business->porcentaje_impuesto : 0;
            $subtotalConDescuento = round($subtotal - $totalDescuentoProductos, 2);

            $descuentoPorcentajeGlobal = 0;
            if ($descuentoActivo && $descuentoPorcentaje > 0) {
                $descuentoPorcentajeGlobal = round($subtotalConDescuento * ((float) $descuentoPorcentaje / 100), 2);
            }

            $tax = round(($subtotalConDescuento - $descuentoPorcentajeGlobal) * ($taxPercentage / 100), 2);
            $total = round($subtotalConDescuento - $descuentoPorcentajeGlobal + $tax, 2);
            $paidAmount = $paymentMethod === 'credito' ? 0 : round((float) $paid, 2);
            $cambio = in_array($paymentMethod, ['credito', 'dividido']) ? 0 : round($paidAmount - $total, 2);

            if ($paymentMethod !== 'credito' && $paymentMethod !== 'dividido' && $paidAmount < $total) {
                throw new \RuntimeException('El monto recibido es insuficiente.');
            }

            if ($paymentMethod === 'dividido' && $pagosDivididos) {
                $sumaPagos = array_sum(array_map(fn($p) => (float) $p['monto'], $pagosDivididos));
                if (round($sumaPagos, 2) !== round($total, 2)) {
                    throw new \RuntimeException('La suma de los pagos divididos debe ser exactamente igual al total. Total: $' . number_format($total, 2) . ', Suma: $' . number_format($sumaPagos, 2));
                }
                $paidAmount = round($total, 2);
                $cambio = 0;
            }

            $descuentoTotal = round($totalDescuentoProductos + $descuentoPorcentajeGlobal, 2);
            $descuentoPorcentajeFinal = $descuentoActivo && $descuentoPorcentaje > 0 ? $descuentoPorcentaje : null;

            $ultimoNumero = (int) Sale::withoutGlobalScopes()
                ->orderByDesc('id')
                ->lockForUpdate()
                ->value('id');
            $numeroComprobante = 'CMP-' . str_pad((string) ($ultimoNumero + 1), 6, '0', STR_PAD_LEFT);

            try {
                $sale = Sale::create([
                    'sucursal_id' => $turnoCajero->sucursal_id,
                    'numero_comprobante' => $numeroComprobante,
                    'clave_idempotencia' => $idempotencyKey,
                    'turno_cajero_id' => $turnoCajero->id,
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
            } catch (\Illuminate\Database\QueryException $e) {
                $existente = Sale::where('clave_idempotencia', $idempotencyKey)->first();

                if ($existente) {
                    return $existente->load('detalles');
                }

                throw $e;
            }

            foreach ($saleItems as $item) {
                $modificadoresFinales = $item['modificadores'] ?? [];
                unset($item['modificadores']);

                $detalle = $sale->detalles()->create($item);

                if (!empty($modificadoresFinales)) {
                    foreach ($modificadoresFinales as $mod) {
                        $detalle->modificadores()->attach($mod['modificador_id'], [
                            'precio_extra' => $mod['precio_extra'],
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

                if ($hasVariantWithStock && $product->maneja_existencias) {
                    $v = $variantes[$item['producto_variante_id']];
                    $v->decrement('stock', $item['cantidad']);
                }
            }

            if ($paymentMethod === 'efectivo') {
                MovimientoEfectivo::create([
                    'negocio_id' => $turnoCajero->negocio_id,
                    'sucursal_id' => $turnoCajero->sucursal_id,
                    'turno_cajero_id' => $turnoCajero->id,
                    'usuario_id' => Auth::id(),
                    'tipo' => 'venta',
                    'monto' => $paidAmount,
                    'motivo' => 'Venta ' . $sale->numero_comprobante,
                    'tipo_referencia' => Sale::class,
                    'id_referencia' => $sale->id,
                ]);

                if ($cambio > 0) {
                    MovimientoEfectivo::create([
                        'negocio_id' => $turnoCajero->negocio_id,
                        'sucursal_id' => $turnoCajero->sucursal_id,
                        'turno_cajero_id' => $turnoCajero->id,
                        'usuario_id' => Auth::id(),
                        'tipo' => 'retiro',
                        'monto' => -$cambio,
                        'motivo' => 'Cambio de venta ' . $sale->numero_comprobante,
                        'tipo_referencia' => Sale::class,
                        'id_referencia' => $sale->id,
                    ]);
                }
            } elseif ($paymentMethod === 'transferencia') {
                MovimientoEfectivo::create([
                    'negocio_id' => $turnoCajero->negocio_id,
                    'sucursal_id' => $turnoCajero->sucursal_id,
                    'turno_cajero_id' => $turnoCajero->id,
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
                        'negocio_id' => $turnoCajero->negocio_id,
                        'sucursal_id' => $turnoCajero->sucursal_id,
                        'turno_cajero_id' => $turnoCajero->id,
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
