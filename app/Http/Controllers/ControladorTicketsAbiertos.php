<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\TicketAbierto;
use App\Models\TurnoCajero;
use App\Services\ContextoNegocio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ControladorTicketsAbiertos extends Controller
{
    public function index(): JsonResponse
    {
        $turno = $this->turnoAbiertoDeCajero();

        $tickets = $turno
            ? TicketAbierto::where('negocio_id', app(ContextoNegocio::class)->id())
                ->where('turno_cajero_id', $turno->id)
                ->with('detalles')
                ->get()
            : collect();

        return response()->json($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->where('negocio_id', app(ContextoNegocio::class)->id())],
            'items.*.producto_variante_id' => ['nullable', 'integer', Rule::exists('producto_variantes', 'id')->where('negocio_id', app(ContextoNegocio::class)->id())],
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.descuento' => 'nullable|numeric|min:0',
            'items.*.modificadores' => 'nullable|array',
        ]);

        $turno = $this->turnoAbiertoDeCajero();

        if (!$turno) {
            return response()->json([
                'success' => false,
                'message' => 'Debes abrir un turno de cajero antes de guardar tickets.',
            ], 422);
        }

        $negocioId = app(ContextoNegocio::class)->id();

        $productIds = collect($request->items)->pluck('producto_id')->unique()->all();
        $varianteIds = collect($request->items)->pluck('producto_variante_id')->filter()->unique()->all();

        $productos = Producto::whereIn('id', $productIds)->get()->keyBy('id');
        $variantes = $varianteIds ? ProductoVariante::whereIn('id', $varianteIds)->get()->keyBy('id') : collect();

        if ($productos->count() !== count($productIds)) {
            return response()->json(['success' => false, 'message' => 'Uno de los productos no estÃ¡ disponible.'], 422);
        }

        foreach ($request->items as $item) {
            $producto = $productos->get($item['producto_id']);

            if (!$producto || !$producto->esta_activo) {
                return response()->json(['success' => false, 'message' => 'Uno de los productos ya no estÃ¡ disponible.'], 422);
            }

            $variante = null;
            if (!empty($item['producto_variante_id'])) {
                $variante = $variantes->get($item['producto_variante_id']);

                if (!$variante || !$variante->esta_activo || (int) $variante->producto_id !== (int) $producto->id) {
                    return response()->json(['success' => false, 'message' => 'Una de las variantes seleccionadas no existe.'], 422);
                }
            }

            $unidades = (int) $item['cantidad'];

            if ($producto->maneja_existencias) {
                if ($variante && $variante->stock !== null && $variante->stock < $unidades) {
                    return response()->json(['success' => false, 'message' => "Existencias insuficientes para la variante {$variante->nombre}."], 422);
                }

                if ((!$variante || $variante->stock === null) && $producto->existencias < $unidades) {
                    return response()->json(['success' => false, 'message' => "Existencias insuficientes para {$producto->nombre}."], 422);
                }
            }
        }

        $ticket = DB::transaction(function () use ($request, $turno, $negocioId, $productos, $variantes) {
            $ticket = TicketAbierto::create([
                'negocio_id' => $negocioId,
                'sucursal_id' => app(ContextoNegocio::class)->sucursalId(),
                'turno_cajero_id' => $turno->id,
                'usuario_id' => auth()->id(),
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
            ]);

            foreach ($request->items as $item) {
                $producto = $productos->get($item['producto_id']);
                $variante = !empty($item['producto_variante_id']) ? $variantes->get($item['producto_variante_id']) : null;

                $unidades = (int) $item['cantidad'];
                $precio = (float) ($variante?->precio ?? $producto->precio);
                $nombreProducto = $producto->nombre . ($variante ? ' - ' . $variante->nombre : '');

                $subtotalBruto = round($precio * $unidades, 2);
                $descuento = round(min((float) ($item['descuento'] ?? 0), $subtotalBruto), 2);

                $ticket->detalles()->create([
                    'negocio_id' => $negocioId,
                    'producto_id' => $producto->id,
                    'producto_variante_id' => $variante?->id,
                    'nombre_producto' => $nombreProducto,
                    'cantidad' => $unidades,
                    'precio' => $precio,
                    'descuento' => $descuento,
                    'subtotal' => round($subtotalBruto - $descuento, 2),
                    'modificadores' => $item['modificadores'] ?? null,
                ]);
            }

            return $ticket;
        });

        return response()->json([
            'success' => true,
            'ticket' => $ticket->load('detalles'),
            'message' => 'Ticket guardado correctamente.',
        ]);
    }

    public function show(TicketAbierto $ticket): JsonResponse
    {
        abort_unless($ticket->negocio_id === app(ContextoNegocio::class)->id(), 404);
        return response()->json($ticket->load('detalles', 'detalles.producto', 'detalles.productoVariante'));
    }

    public function destroy(TicketAbierto $ticket): JsonResponse
    {
        abort_unless($ticket->negocio_id === app(ContextoNegocio::class)->id(), 404);
        $ticket->delete();
        return response()->json([
            'success' => true,
            'message' => 'Ticket eliminado.',
        ]);
    }

    private function turnoAbiertoDeCajero(): ?TurnoCajero
    {
        return TurnoCajero::where('usuario_id', Auth::id())
            ->where('estado', 'abierta')
            ->latest('id')
            ->first();
    }
}
