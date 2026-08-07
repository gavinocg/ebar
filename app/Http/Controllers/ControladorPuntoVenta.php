<?php

namespace App\Http\Controllers;

use App\Models\Producto as Product;
use App\Models\Categoria as Category;
use App\Models\Impresora as Printer;
use App\Models\ConfiguracionNegocio as BusinessSetting;
use App\Models\MembresiaNegocio;
use App\Models\Sucursal;
use App\Models\TurnoCaja;
use App\Models\Venta as Sale;
use App\Services\ContextoNegocio;
use Illuminate\Support\Facades\Auth;
use App\Services\ServicioImpresoraTermica;
use App\Services\ServicioCobro;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ControladorPuntoVenta extends Controller
{
    public function index()
    {
        if (!session('pos_desbloqueado')) {
            return view('pos.lock');
        }

        $usuario = auth()->user();

        $turnoAbierto = TurnoCaja::where('usuario_id', $usuario->id)
            ->where('estado', 'abierta')
            ->exists();

        if (!$turnoAbierto) {
            $business = BusinessSetting::obtenerConfiguracion();
            $printer = Printer::predeterminada()->first();
            $sucursales = Sucursal::where('esta_activa', true)->orderBy('nombre')->get();
            $sucursalActual = $sucursales->firstWhere('id', app(ContextoNegocio::class)->sucursalId());
            return view('pos.apertura', compact('business', 'printer', 'sucursales', 'sucursalActual'));
        }

        $sucursalId = app(ContextoNegocio::class)->sucursalId();

        $products = Product::with('categoria')
            ->where('esta_activo', true)
            ->where(function ($q) use ($sucursalId) {
                $q->whereNull('sucursal_id')
                    ->when($sucursalId, fn ($query) => $query->orWhere('sucursal_id', $sucursalId));
            })
            ->orderBy('nombre')
            ->get();
        $categories = Category::where('esta_activa', true)->orderBy('orden')->orderBy('nombre')->get();
        $printer = Printer::predeterminada()->first();
        $business = BusinessSetting::obtenerConfiguracion();

        $sucursales = Sucursal::where('esta_activa', true)->orderBy('nombre')->get();
        $sucursalActual = $sucursales->firstWhere('id', $sucursalId);

        return view('pos.index', compact('products', 'categories', 'printer', 'business', 'sucursales', 'sucursalActual'));
    }

    public function buscar(Request $request)
    {
        $query = $request->get('q', '');
        $sucursalId = app(ContextoNegocio::class)->sucursalId();
        $products = Product::with('categoria')
            ->where('esta_activo', true)
            ->where(function ($q) use ($sucursalId) {
                $q->whereNull('sucursal_id')
                    ->when($sucursalId, fn ($query) => $query->orWhere('sucursal_id', $sucursalId));
            })
            ->where(function ($q) use ($query) {
                $q->where('nombre', 'like', "%{$query}%")
                  ->orWhere('codigo_barras', $query);
            })
            ->limit(20)
            ->get();

        return response()->json($products);
    }

    public function ventasHoy()
    {
        $hoy = now()->format('Y-m-d');

        $ventas = Sale::where('usuario_id', Auth::id())
            ->whereDate('created_at', $hoy)
            ->orderBy('created_at', 'desc')
            ->get(['numero_comprobante', 'metodo_pago', 'total']);

        $total = $ventas->sum('total');

        return view('pos.ventas-hoy', compact('ventas', 'total'));
    }

    public function desbloquear(Request $request)
    {
        $datos = $request->validate([
            'pin' => 'required|digits:4',
        ]);

        $usuario = $request->user();

        if (blank($usuario->pin)) {
            return redirect()->back()->withErrors(['pin' => 'Este usuario no tiene PIN configurado.']);
        }

        if (! password_verify($datos['pin'], $usuario->pin)) {
            return redirect()->back()->withErrors(['pin' => 'PIN incorrecto.']);
        }

        $request->session()->put('pos_desbloqueado', true);

        return redirect()->route('punto_venta.inicio');
    }

    public function bloquear()
    {
        session(['cajero_pin_id' => Auth::id()]);
        session()->forget('pos_desbloqueado');

        return redirect()->route('inicio_sesion.pin');
    }

    public function cobrar(Request $request, ServicioCobro $servicioCobro)
    {
        try {
            $request->validate([
                'items' => 'required|array|min:1|max:100',
                'items.*.producto_id' => 'required|integer|distinct|exists:productos,id',
                'items.*.cantidad' => 'required|integer|min:1|max:10000',
                'metodo_pago' => 'required|in:efectivo,credito,transferencia',
                'pagado' => 'required|numeric|min:0|max:99999999.99',
                'notas' => 'nullable|string|max:1000',
                'clave_idempotencia' => 'required|string|max:100',
                'cliente_id' => 'required_if:metodo_pago,credito|nullable|integer|exists:clientes,id',
                'descripcion_cliente' => 'required_if:metodo_pago,credito|nullable|string|max:255',
                'entidad_financiera' => 'required_if:metodo_pago,transferencia|nullable|string|max:100',
                'numero_comprobante_pago' => 'required_if:metodo_pago,transferencia|nullable|string|max:100',
            ]);

            $sucursalId = app(ContextoNegocio::class)->sucursalId();

            $membresiaCajero = MembresiaNegocio::where('usuario_id', Auth::id())
                ->where('rol', 'cajero')
                ->where('esta_activa', true)
                ->first();

            if ($membresiaCajero && $membresiaCajero->sucursal_id && (int) $membresiaCajero->sucursal_id !== (int) $sucursalId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes cobrar en esta sucursal. Tu sucursal asignada es: ' . ($membresiaCajero->sucursal?->nombre ?? 'N/A'),
                ], 403);
            }

            $sale = $servicioCobro->crear(
                $request->input('items'),
                $request->string('metodo_pago')->toString(),
                $request->input('pagado'),
                $request->input('notas'),
                $request->string('clave_idempotencia')->toString(),
                $request->input('cliente_id'),
                $request->input('descripcion_cliente'),
                $request->input('entidad_financiera'),
                $request->input('numero_comprobante_pago'),
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
