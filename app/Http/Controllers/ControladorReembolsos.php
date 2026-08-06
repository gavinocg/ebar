<?php

namespace App\Http\Controllers;

use App\Models\Reembolso;
use App\Models\Venta;
use App\Services\ServicioReembolso;
use Illuminate\Http\Request;

class ControladorReembolsos extends Controller
{
    public function index()
    {
        $this->authorize('administrar', Venta::class);

        $reembolsos = Reembolso::with('venta', 'usuario', 'autorizadoPor')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('reembolsos.index', compact('reembolsos'));
    }

    public function crear(Request $request, Venta $venta, ServicioReembolso $servicioReembolso)
    {
        $this->authorize('reembolsar', $venta);

        $validados = $request->validate([
            'tipo' => 'required|in:parcial,total',
            'motivo' => 'required|string|max:500',
            'metodo' => 'required|in:efectivo,transferencia,credito',
            'items' => 'required|array|min:1',
            'items.*' => 'required|integer|min:1',
        ]);

        try {
            if ($validados['tipo'] === 'total') {
                $items = $venta->detalles->pluck('cantidad', 'id')->all();
            } else {
                $items = array_filter($validados['items'], fn ($cantidad) => $cantidad > 0);
            }

            $reembolso = $servicioReembolso->crear(
                $venta,
                $validados['tipo'],
                $items,
                $validados['motivo'],
                $validados['metodo'],
                \Illuminate\Support\Facades\Auth::id(),
            );

            app(\App\Services\RegistradorAuditoria::class)->registrar(
                'ventas',
                'reembolso',
                "Reembolso {$validados['tipo']} de {$venta->numero_comprobante} por {$reembolso->monto}",
                ['venta_id' => $venta->id, 'reembolso_id' => $reembolso->id, 'tipo' => $validados['tipo']],
                Reembolso::class,
                $reembolso->id,
            );

            return redirect()->back()->with('success', 'Reembolso registrado correctamente.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
}