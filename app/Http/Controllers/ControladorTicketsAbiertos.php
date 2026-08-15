<?php

namespace App\Http\Controllers;

use App\Models\TicketAbierto;
use App\Models\TicketAbiertoDetalle;
use App\Services\ContextoNegocio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ControladorTicketsAbiertos extends Controller
{
    public function index(): JsonResponse
    {
        $tickets = TicketAbierto::where('negocio_id', app(ContextoNegocio::class)->id())
            ->where('turno_caja_id', session('turno_caja_id'))
            ->with('detalles')
            ->get();

        return response()->json($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.producto_variante_id' => 'nullable|integer|exists:producto_variantes,id',
            'items.*.nombre_producto' => 'required|string',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio' => 'required|numeric|min:0',
            'items.*.descuento' => 'nullable|numeric|min:0',
            'items.*.modificadores' => 'nullable|array',
        ]);

        $ticket = DB::transaction(function () use ($request) {
            $ticket = TicketAbierto::create([
                'negocio_id' => app(ContextoNegocio::class)->id(),
                'sucursal_id' => app(ContextoNegocio::class)->sucursalId(),
                'turno_caja_id' => session('turno_caja_id'),
                'usuario_id' => auth()->id(),
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
            ]);

            foreach ($request->items as $item) {
                $descuento = $item['descuento'] ?? 0;
                $subtotal = ($item['precio'] * $item['cantidad']) - $descuento;

                $ticket->detalles()->create([
                    'negocio_id' => app(ContextoNegocio::class)->id(),
                    'producto_id' => $item['producto_id'],
                    'producto_variante_id' => $item['producto_variante_id'] ?? null,
                    'nombre_producto' => $item['nombre_producto'],
                    'cantidad' => $item['cantidad'],
                    'precio' => $item['precio'],
                    'descuento' => $descuento,
                    'subtotal' => $subtotal,
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
        return response()->json($ticket->load('detalles'));
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
}
