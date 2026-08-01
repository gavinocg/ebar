<?php

namespace App\Http\Controllers;

use App\Models\Producto as Product;
use App\Models\Categoria as Category;
use App\Models\Impresora as Printer;
use App\Models\ConfiguracionNegocio as BusinessSetting;
use App\Services\ServicioImpresoraTermica;
use App\Services\ServicioCobro;
use Illuminate\Http\Request;

class ControladorPuntoVenta extends Controller
{
    public function index()
    {
        $products = Product::with('categoria')
            ->where('esta_activo', true)
            ->orderBy('nombre')
            ->get();
        $categories = Category::where('esta_activa', true)->orderBy('orden')->orderBy('nombre')->get();
        $printer = Printer::predeterminada()->first();
        $business = BusinessSetting::obtenerConfiguracion();
        
        return view('pos.index', compact('products', 'categories', 'printer', 'business'));
    }

    public function buscar(Request $request)
    {
        $query = $request->get('q', '');
        $products = Product::with('categoria')
            ->where('esta_activo', true)
            ->where(function ($q) use ($query) {
                $q->where('nombre', 'like', "%{$query}%")
                  ->orWhere('codigo_barras', $query);
            })
            ->limit(20)
            ->get();

        return response()->json($products);
    }

    public function cobrar(Request $request, ServicioCobro $servicioCobro)
    {
        try {
            $request->validate([
                'items' => 'required|array|min:1|max:100',
                'items.*.producto_id' => 'required|integer|distinct|exists:productos,id',
                'items.*.cantidad' => 'required|integer|min:1|max:10000',
                'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia',
                'pagado' => 'required|numeric|min:0|max:99999999.99',
                'notas' => 'nullable|string|max:1000',
                'clave_idempotencia' => 'required|string|max:100',
            ]);

            $sale = $servicioCobro->crear(
                $request->input('items'),
                $request->string('metodo_pago')->toString(),
                $request->input('pagado'),
                $request->input('notas'),
                $request->string('clave_idempotencia')->toString(),
            );

            $sale->load('detalles');
            $printer = Printer::predeterminada()->first();

            if ($printer) {
                if ($printer->esConvencional()) {
                    $viewName = in_array($printer->ancho_papel, ['a4', 'letter']) ? 'printers.ticket-a4' : 'printers.ticket-a5';
                    $ticketHtml = view($viewName, compact('sale'))->render();
                    return response()->json([
                        'success' => true,
                        'type' => 'normal',
                        'sale' => $sale,
                        'ticket_html' => $ticketHtml,
                    ]);
                } else {
                    $servicioImpresora = new ServicioImpresoraTermica($printer);
                    $ticketData = $servicioImpresora->imprimirComprobante($sale);
                    $connectionData = $servicioImpresora->obtenerDatosConexion();
                    $ticketView = $printer->ancho_papel === '58mm' ? 'printers.ticket-58' : 'printers.ticket-a4';
                    $ticketHtml = view($ticketView, compact('sale'))->render();
                    
                    return response()->json([
                        'success' => true,
                        'type' => 'thermal',
                        'sale' => $sale,
                        'ticket' => base64_encode($ticketData),
                        'ticket_html' => $ticketHtml,
                        'printer' => $connectionData,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'type' => 'none',
                'sale' => $sale,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo procesar la venta. Inténtalo de nuevo.',
            ], 500);
        }
    }
}
