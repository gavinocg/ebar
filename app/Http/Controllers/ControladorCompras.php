<?php

namespace App\Http\Controllers;

use App\Models\DetalleOrdenCompra;
use App\Models\MovimientoInventario;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\ContextoNegocio;
use App\Services\RegistradorAuditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ControladorCompras extends Controller
{
    public function indexProveedores(): View
    {
        $proveedores = Proveedor::orderBy('nombre')->get();

        return view('compras.proveedores', compact('proveedores'));
    }

    public function storeProveedor(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'ruc' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:255',
        ]);

        Proveedor::create($datos);

        return back()->with('success', 'Proveedor registrado.');
    }

    public function updateProveedor(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'ruc' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:255',
            'esta_activo' => 'nullable|boolean',
        ]);

        $proveedor->update($datos);

        return back()->with('success', 'Proveedor actualizado.');
    }

    public function destroyProveedor(Proveedor $proveedor): RedirectResponse
    {
        $proveedor->delete();

        return back()->with('success', 'Proveedor eliminado.');
    }

    public function ordenes(): View
    {
        $ordenes = OrdenCompra::with('proveedor', 'detalles')->orderByDesc('created_at')->get();
        $proveedores = Proveedor::where('esta_activo', true)->orderBy('nombre')->get();
        $productos = Producto::where('maneja_existencias', true)->orderBy('nombre')->get();

        return view('compras.ordenes', compact('ordenes', 'proveedores', 'productos'));
    }

    public function storeOrden(Request $request, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $datos = $request->validate([
            'proveedor_id' => 'required|integer|exists:proveedores,id',
            'fecha' => 'nullable|date',
            'notas' => 'nullable|string|max:255',
            'items' => 'required|array|min:1|max:100',
            'items.*.producto_id' => 'required|integer|distinct|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1|max:100000',
            'items.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($datos, $auditoria) {
            $maxNumero = (int) DB::select("SELECT COALESCE(CAST(SUBSTRING(numero, 4) AS UNSIGNED), 0) as max_num FROM ordenes_compra ORDER BY id DESC LIMIT 1")[0]->max_num ?? 0;
            $numero = 'OC-' . str_pad((string) ($maxNumero + 1), 5, '0', STR_PAD_LEFT);

            $total = 0;
            foreach ($datos['items'] as $item) {
                $total += round((float) $item['cantidad'] * (float) $item['precio_unitario'], 2);
            }

            $orden = OrdenCompra::create([
                'proveedor_id' => $datos['proveedor_id'],
                'usuario_id' => auth()->id(),
                'numero' => $numero,
                'fecha' => $datos['fecha'] ?? now()->toDateString(),
                'estado' => 'pendiente',
                'subtotal' => $total,
                'impuesto' => 0,
                'total' => $total,
                'notas' => $datos['notas'] ?? null,
            ]);

            foreach ($datos['items'] as $item) {
                $subtotal = round((float) $item['cantidad'] * (float) $item['precio_unitario'], 2);
                $orden->detalles()->create([
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $subtotal,
                ]);
            }

            $auditoria->registrar('compras', 'crear_orden', 'Orden de compra ' . $orden->numero, [
                'proveedor_id' => $orden->proveedor_id,
                'total' => $total,
            ], OrdenCompra::class, $orden->id);

            return back()->with('success', 'Orden de compra registrada.');
        });
    }

    public function recibir(OrdenCompra $ordenCompra, RegistradorAuditoria $auditoria): RedirectResponse
    {
        abort_unless(in_array($ordenCompra->estado, ['pendiente', 'recepcion']), 422, 'Esta orden ya fue recibida.');

        $negocioId = app(ContextoNegocio::class)->id();
        abort_unless($ordenCompra->negocio_id === $negocioId, 404);

        DB::transaction(function () use ($ordenCompra, $auditoria) {
            $productoIds = $ordenCompra->detalles->pluck('producto_id')->filter()->values()->all();
            $productosLock = Producto::whereIn('id', $productoIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($ordenCompra->detalles as $detalle) {
                if (!$detalle->producto?->maneja_existencias) {
                    continue;
                }

                $producto = $productosLock->get($detalle->producto_id);
                if (!$producto) {
                    continue;
                }

                $anterior = $producto->existencias;
                $nueva = $anterior + $detalle->cantidad;
                $producto->update(['existencias' => $nueva]);

                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'usuario_id' => auth()->id(),
                    'tipo' => 'mercancias',
                    'cantidad' => $detalle->cantidad,
                    'existencias_anteriores' => $anterior,
                    'existencias_posteriores' => $nueva,
                    'tipo_referencia' => OrdenCompra::class,
                    'id_referencia' => $ordenCompra->id,
                    'notas' => 'Recepción de orden ' . $ordenCompra->numero,
                ]);
            }

            $ordenCompra->update(['estado' => 'recibida', 'recibida_en' => now()]);

            $auditoria->registrar('compras', 'recepcion_orden', 'Recepción de orden ' . $ordenCompra->numero, [
                'orden_id' => $ordenCompra->id,
            ], OrdenCompra::class, $ordenCompra->id);
        });

        return back()->with('success', 'Mercancía recibida y existencias actualizadas.');
    }

    public function destroyOrden(OrdenCompra $orden): RedirectResponse
    {
        if ($orden->estado === 'recibida') {
            return back()->withErrors(['orden' => 'No se puede eliminar una orden ya recibida.']);
        }

        $orden->delete();

        return back()->with('success', 'Orden de compra eliminada.');
    }
}