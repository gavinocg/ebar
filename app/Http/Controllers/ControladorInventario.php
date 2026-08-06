<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Services\RegistradorAuditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ControladorInventario extends Controller
{
    public function historial(Request $request): View
    {
        $movimientos = MovimientoInventario::with('producto', 'usuario')
            ->when($request->filled('producto_id'), fn ($q) => $q->where('producto_id', $request->input('producto_id')))
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->input('tipo')))
            ->when($request->filled('sucursal_id'), function ($q) use ($request) {
                $q->whereHas('producto', fn ($p) => $p->where('sucursal_id', $request->input('sucursal_id')));
            })
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('inventario.historial', [
            'movimientos' => $movimientos,
            'productos' => Producto::orderBy('nombre')->get(),
            'sucursales' => Sucursal::orderBy('nombre')->get(),
        ]);
    }

    public function ajustar(Request $request, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $datos = $request->validate([
            'producto_id' => 'required|integer|exists:productos,id',
            'cantidad' => 'required|integer|min:1|max:100000',
            'tipo' => 'required|in:entrada,ajuste,ajuste_negativo,devolucion,mercancias',
            'motivo' => 'required|string|max:255',
        ]);

        $producto = Producto::lockForUpdate()->findOrFail($datos['producto_id']);

        if (!$producto->maneja_existencias) {
            return back()->withErrors(['producto_id' => 'Este producto no controla existencias.']);
        }

        $tipo = $datos['tipo'];
        $cantidad = (int) $datos['cantidad'];

        $cambia = [
            'ajuste' => true,           // entrada
            'mercancias' => true,       // entrada
            'devolucion' => true,       // entrada
            'ajuste_negativo' => false, // salida
        ];
        $esEntrada = $cambia[$tipo] ?? true;

        $anterior = $producto->existencias;
        $nueva = $esEntrada ? $anterior + $cantidad : $anterior - $cantidad;

        if ($nueva < 0) {
            return back()->withErrors(['cantidad' => 'El ajuste dejaría existencias negativas.']);
        }

        DB::transaction(function () use ($producto, $tipo, $cantidad, $nueva, $anterior, $datos): void {
            $producto->update(['existencias' => $nueva]);

            MovimientoInventario::create([
                'producto_id' => $producto->id,
                'usuario_id' => auth()->id(),
                'tipo' => $tipo,
                'cantidad' => $anterior < $nueva ? $cantidad : -$cantidad,
                'existencias_anteriores' => $anterior,
                'existencias_posteriores' => $nueva,
                'tipo_referencia' => 'ajuste_manual',
                'notas' => $datos['motivo'],
            ]);

            $auditoria->registrar('inventario', 'ajuste_manual', $datos['motivo'], [
                'producto_id' => $producto->id,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'anterior' => $anterior,
                'nueva' => $nueva,
            ], 'ajuste_manual', $producto->id);
        });

        return back()->with('success', 'Ajuste de inventario registrado.');
    }
}