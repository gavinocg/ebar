<?php

namespace App\Http\Controllers;

use App\Models\Impresora as Printer;
use App\Services\ServicioImpresoraTermica;
use Illuminate\Http\Request;

class ControladorImpresoras extends Controller
{
    public function index()
    {
        $printers = Printer::orderBy('es_predeterminada', 'desc')->orderBy('nombre')->get();
        return view('printers.index', compact('printers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo_conexion' => 'required|in:bluetooth,wifi,lan,normal',
            'direccion' => 'nullable|string',
            'puerto' => 'nullable|integer|min:1|max:65535',
            'ancho_papel' => 'required|in:58mm,80mm,a4,a5,letter',
        ]);

        if ($request->tipo_conexion === 'normal') {
            $request->merge(['direccion' => null, 'puerto' => null]);
        }

        if ($request->es_predeterminada) {
            Printer::where('es_predeterminada', true)->update(['es_predeterminada' => false]);
        }

        Printer::create($request->all());

        return redirect()->route('impresoras.index')->with('success', 'Impresora agregada');
    }

    public function update(Request $request, Printer $printer)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo_conexion' => 'required|in:bluetooth,wifi,lan,normal',
            'direccion' => 'nullable|string',
            'puerto' => 'nullable|integer|min:1|max:65535',
            'ancho_papel' => 'required|in:58mm,80mm,a4,a5,letter',
        ]);

        if ($request->tipo_conexion === 'normal') {
            $request->merge(['direccion' => null, 'puerto' => null]);
        }

        if ($request->es_predeterminada) {
            Printer::where('es_predeterminada', true)
                ->where('id', '!=', $printer->id)
                ->update(['es_predeterminada' => false]);
        }

        $printer->update($request->all());

        return redirect()->route('impresoras.index')->with('success', 'Impresora actualizada');
    }

    public function destroy(Printer $printer)
    {
        $printer->delete();
        return redirect()->route('impresoras.index')->with('success', 'Impresora eliminada');
    }

    public function probar(Printer $printer)
    {
        try {
            if ($printer->esConvencional()) {
                $viewName = in_array($printer->ancho_papel, ['a4', 'letter']) ? 'printers.test-ticket-a4' : 'printers.test-ticket-html';
                return response()->json([
                    'success' => true,
                    'type' => 'normal',
                    'ticket_html' => view($viewName, [
                        'printerName' => $printer->nombre,
                        'date' => now()->format('d/m/Y H:i:s'),
                        'paperSize' => strtoupper($printer->ancho_papel),
                    ])->render(),
                ]);
            }

            $servicioImpresora = new ServicioImpresoraTermica($printer);
            $ticketData = $servicioImpresora->imprimirComprobantePrueba();
            $connectionData = $servicioImpresora->obtenerDatosConexion();

            return response()->json([
                'success' => true,
                'type' => 'thermal',
                'ticket' => base64_encode($ticketData),
                'printer' => $connectionData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
