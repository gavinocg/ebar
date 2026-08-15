<?php

namespace App\Http\Controllers;

use App\Models\ConteoInventario;
use App\Models\DetalleConteo;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\ContextoNegocio;
use App\Services\RegistradorAuditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ControladorConteos extends Controller
{
    public function index(): View
    {
        $conteos = ConteoInventario::withCount('detalles')->orderByDesc('created_at')->get();

        return view('inventario.conteos', compact('conteos'));
    }

    public function crear(): View
    {
        $productos = Producto::where('maneja_existencias', true)->orderBy('nombre')->get();

        return view('inventario.conteo-crear', compact('productos'));
    }

    public function store(Request $request, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $datos = $request->validate([
            'notas' => 'nullable|string|max:255',
            'productos' => 'required|array|min:1|max:200',
            'productos.*.producto_id' => 'required|integer|exists:productos,id',
            'productos.*.existencias_reales' => 'required|integer|min:0|max:1000000',
        ]);

        return DB::transaction(function () use ($datos, $auditoria) {
            $conteo = ConteoInventario::create([
                'usuario_id' => auth()->id(),
                'numero' => 'CNT-' . str_pad((string) (ConteoInventario::max('id') + 1), 5, '0', STR_PAD_LEFT),
                'fecha' => now()->toDateString(),
                'estado' => 'borrador',
                'notas' => $datos['notas'] ?? null,
            ]);

            foreach ($datos['productos'] as $item) {
                $producto = Producto::find($item['producto_id']);
                $sistema = (int) $producto->existencias;
                $reales = (int) $item['existencias_reales'];

                $conteo->detalles()->create([
                    'producto_id' => $producto->id,
                    'existencias_sistema' => $sistema,
                    'existencias_reales' => $reales,
                    'diferencia' => $reales - $sistema,
                ]);
            }

            $conteo->update(['estado' => 'abierto']);

            $auditoria->registrar('inventario', 'crear_conteo', 'Conteo ' . $conteo->numero, [
                'detalles' => $conteo->detalles()->count(),
            ], ConteoInventario::class, $conteo->id);

            return redirect()->route('conteos.index')->with('success', 'Conteo creado. Aplica los ajustes para actualizar existencias.');
        });
    }

    public function aplicar(ConteoInventario $conteo, RegistradorAuditoria $auditoria): RedirectResponse
    {
        abort_if($conteo->estado === 'aplicado', 422, 'Este conteo ya fue aplicado.');

        $negocioId = app(ContextoNegocio::class)->id();
        abort_unless($conteo->negocio_id === $negocioId, 404);

        DB::transaction(function () use ($conteo, $auditoria) {
            $productoIds = $conteo->detalles->pluck('producto_id')->filter()->values()->all();
            $productosLock = Producto::whereIn('id', $productoIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($conteo->detalles as $detalle) {
                $diferencia = $detalle->diferencia;

                if ($diferencia === 0 || !$detalle->producto?->maneja_existencias) {
                    continue;
                }

                $producto = $productosLock->get($detalle->producto_id);
                if (!$producto) {
                    continue;
                }

                $anterior = $producto->existencias;
                $nueva = $anterior + $diferencia;

                $producto->update(['existencias' => $nueva]);

                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'usuario_id' => auth()->id(),
                    'tipo' => 'ajuste',
                    'cantidad' => $diferencia,
                    'existencias_anteriores' => $anterior,
                    'existencias_posteriores' => $nueva,
                    'tipo_referencia' => ConteoInventario::class,
                    'id_referencia' => $conteo->id,
                    'notas' => 'Conteo físico ' . $conteo->numero,
                ]);
            }

            $conteo->update(['estado' => 'aplicado', 'aplicado_en' => now()]);

            $auditoria->registrar('inventario', 'aplicar_conteo', 'Aplicación de conteo ' . $conteo->numero, [], ConteoInventario::class, $conteo->id);
        });

        return back()->with('success', 'Conteo aplicado y existencias ajustadas.');
    }
}