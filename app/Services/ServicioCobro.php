<?php

namespace App\Services;

use App\Models\ConfiguracionNegocio as BusinessSetting;
use App\Models\MovimientoInventario as InventoryMovement;
use App\Models\Producto as Product;
use App\Models\Venta as Sale;
use App\Models\TurnoCaja;
use App\Models\MovimientoEfectivo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServicioCobro
{
    public function crear(array $itemsData, string $paymentMethod, string $paid, ?string $notes, string $idempotencyKey): Sale
    {
        return DB::transaction(function () use ($itemsData, $paymentMethod, $paid, $notes, $idempotencyKey) {
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
                ->groupBy('producto_id')
                ->map(fn ($items) => $items->sum('cantidad'));
            $products = Product::whereIn('id', $quantities->keys()->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $business = BusinessSetting::obtenerConfiguracion();
            $subtotal = 0;
            $saleItems = [];

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);

                if (!$product || !$product->esta_activo) {
                    throw new \RuntimeException('Uno de los productos ya no está disponible.');
                }

                if ($product->maneja_existencias && $product->existencias < $quantity) {
                    throw new \RuntimeException("Existencias insuficientes para {$product->nombre}.");
                }

                $itemSubtotal = round((float) $product->precio * $quantity, 2);
                $subtotal = round($subtotal + $itemSubtotal, 2);
                $saleItems[] = [
                    'producto_id' => $product->id,
                    'nombre_producto' => $product->nombre,
                    'cantidad' => $quantity,
                    'precio' => $product->precio,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $taxEnabled = (bool) $business->cobrar_impuesto;
            $taxPercentage = $taxEnabled ? (float) $business->porcentaje_impuesto : 0;
            $tax = round($subtotal * ($taxPercentage / 100), 2);
            $total = round($subtotal + $tax, 2);
            $paidAmount = round((float) $paid, 2);

            if ($paidAmount < $total) {
                throw new \RuntimeException('El monto recibido es insuficiente.');
            }

            $sale = Sale::create([
                'numero_comprobante' => 'PENDING-' . Str::uuid(),
                'clave_idempotencia' => $idempotencyKey,
                'turno_caja_id' => $turnoCaja->id,
                'usuario_id' => Auth::id(),
                'subtotal' => $subtotal,
                'impuesto' => $tax,
                'impuesto_habilitado' => $taxEnabled,
                'porcentaje_impuesto' => $taxPercentage,
                'total' => $total,
                'metodo_pago' => $paymentMethod,
                'pagado' => $paidAmount,
                'cambio' => round($paidAmount - $total, 2),
                'notas' => $notes,
            ]);

            $sale->update([
                'numero_comprobante' => 'CMP-' . str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
            ]);

            foreach ($saleItems as $item) {
                $sale->detalles()->create($item);
                $product = $products[$item['producto_id']];
                if ($product->maneja_existencias) {
                    $stockBefore = $product->existencias;
                    $product->decrement('existencias', $item['cantidad']);

                    InventoryMovement::create([
                        'producto_id' => $product->id,
                        'tipo' => 'venta',
                        'cantidad' => -$item['cantidad'],
                        'existencias_anteriores' => $stockBefore,
                        'existencias_posteriores' => $stockBefore - $item['cantidad'],
                        'tipo_referencia' => Sale::class,
                        'id_referencia' => $sale->id,
                    ]);
                }
            }

            if ($paymentMethod === 'efectivo') {
                MovimientoEfectivo::create([
                    'caja_id' => $turnoCaja->caja_id,
                    'turno_caja_id' => $turnoCaja->id,
                    'usuario_id' => Auth::id(),
                    'tipo' => 'venta',
                    'monto' => $paidAmount,
                    'motivo' => 'Venta ' . $sale->numero_comprobante,
                    'tipo_referencia' => Sale::class,
                    'id_referencia' => $sale->id,
                ]);
            }

            return $sale->load('detalles');
        });
    }
}
