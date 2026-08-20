<?php

namespace App\Http\Controllers;

use App\Models\Impresora as Printer;
use App\Models\Sucursal;
use App\Services\ContextoNegocio;
use App\Services\ServicioImpresoraTermica;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ControladorImpresoras extends Controller
{
    public function index()
    {
        $printers = Printer::orderBy('es_predeterminada', 'desc')->orderBy('nombre')->get();
        $sucursales = Sucursal::orderBy('nombre')->get();
        return view('printers.index', compact('printers', 'sucursales'));
    }

    public function store(Request $request)
    {
        $negocioId = app(ContextoNegocio::class)->id();

        $request->validate([
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursales', 'id')->where('negocio_id', $negocioId)],
            'nombre' => 'required|string|max:255',
            'tipo_conexion' => 'required|in:bluetooth',
            'ancho_papel' => 'required|in:58mm',
            'es_predeterminada' => 'nullable|boolean',
            'esta_activa' => 'nullable|boolean',
        ]);

        if ($request->es_predeterminada) {
            Printer::where('es_predeterminada', true)->update(['es_predeterminada' => false]);
        }

        $datos = $request->validated();
        $datos['esta_activa'] = $request->boolean('esta_activa');

        Printer::create($datos);

        return redirect()->route('impresoras.index')->with('success', 'Impresora agregada');
    }

    public function update(Request $request, Printer $printer)
    {
        $negocioId = app(ContextoNegocio::class)->id();

        $request->validate([
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursales', 'id')->where('negocio_id', $negocioId)],
            'nombre' => 'required|string|max:255',
            'tipo_conexion' => 'required|in:bluetooth',
            'ancho_papel' => 'required|in:58mm',
            'es_predeterminada' => 'nullable|boolean',
            'esta_activa' => 'nullable|boolean',
        ]);

        if ($request->es_predeterminada) {
            Printer::where('es_predeterminada', true)
                ->where('id', '!=', $printer->id)
                ->update(['es_predeterminada' => false]);
        }

        $datos = $request->validated();
        $datos['esta_activa'] = $request->boolean('esta_activa');

        $printer->update($datos);

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
            $servicioImpresora = new ServicioImpresoraTermica($printer);
            $ticketData = $servicioImpresora->imprimirComprobantePrueba();
            $connectionData = $servicioImpresora->obtenerDatosConexion();

            return response()->json([
                'success' => true,
                'type' => 'thermal',
                'ticket' => base64_encode($ticketData),
                'datos' => $connectionData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
