<?php

namespace App\Http\Controllers;

use App\Models\Printer;
use App\Services\ThermalPrinterService;
use Illuminate\Http\Request;

class PrinterController extends Controller
{
    public function index()
    {
        $printers = Printer::orderBy('is_default', 'desc')->orderBy('name')->get();
        return view('printers.index', compact('printers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'connection_type' => 'required|in:bluetooth,wifi,lan,normal',
            'address' => 'nullable|string',
            'port' => 'nullable|integer|min:1|max:65535',
            'paper_width' => 'required|in:58mm,80mm,a4,a5,letter',
        ]);

        if ($request->connection_type === 'normal') {
            $request->merge(['address' => null, 'port' => null]);
        }

        if ($request->is_default) {
            Printer::where('is_default', true)->update(['is_default' => false]);
        }

        Printer::create($request->all());

        return redirect()->route('printers.index')->with('success', 'Impresora agregada');
    }

    public function update(Request $request, Printer $printer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'connection_type' => 'required|in:bluetooth,wifi,lan,normal',
            'address' => 'nullable|string',
            'port' => 'nullable|integer|min:1|max:65535',
            'paper_width' => 'required|in:58mm,80mm,a4,a5,letter',
        ]);

        if ($request->connection_type === 'normal') {
            $request->merge(['address' => null, 'port' => null]);
        }

        if ($request->is_default) {
            Printer::where('is_default', true)
                ->where('id', '!=', $printer->id)
                ->update(['is_default' => false]);
        }

        $printer->update($request->all());

        return redirect()->route('printers.index')->with('success', 'Impresora actualizada');
    }

    public function destroy(Printer $printer)
    {
        $printer->delete();
        return redirect()->route('printers.index')->with('success', 'Impresora eliminada');
    }

    public function test(Printer $printer)
    {
        try {
            if ($printer->isNormal()) {
                $viewName = in_array($printer->paper_width, ['a4', 'letter']) ? 'printers.test-ticket-a4' : 'printers.test-ticket-html';
                return response()->json([
                    'success' => true,
                    'type' => 'normal',
                    'ticket_html' => view($viewName, [
                        'printerName' => $printer->name,
                        'date' => now()->format('d/m/Y H:i:s'),
                        'paperSize' => strtoupper($printer->paper_width),
                    ])->render(),
                ]);
            }

            $printerService = new ThermalPrinterService($printer);
            $ticketData = $printerService->printTestTicket();
            $connectionData = $printerService->getConnectionData();

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
