<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Category;
use App\Models\Printer;
use App\Models\BusinessSetting;
use App\Services\ThermalPrinterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index()
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $categories = Category::orderBy('name')->get();
        $printer = Printer::default()->first();
        $business = BusinessSetting::getSettings();
        
        return view('pos.index', compact('products', 'categories', 'printer', 'business'));
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $products = Product::with('category')
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('barcode', $query);
            })
            ->limit(20)
            ->get();

        return response()->json($products);
    }

    public function checkout(Request $request)
    {
        try {
            $request->validate([
                'items' => 'required',
                'payment_method' => 'required|string',
                'paid' => 'required|numeric|min:0',
            ]);

            $itemsData = is_string($request->items) ? json_decode($request->items, true) : $request->items;

            if (!$itemsData || !is_array($itemsData)) {
                return response()->json(['success' => false, 'message' => 'Datos de items inválidos'], 400);
            }

            $sale = DB::transaction(function () use ($itemsData, $request) {
                $subtotal = 0;
                $items = [];
                $business = BusinessSetting::getSettings();

                foreach ($itemsData as $item) {
                    $product = Product::findOrFail($item['product_id']);

                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("Stock insuficiente para {$product->name}");
                    }

                    $itemSubtotal = $product->price * $item['quantity'];
                    $subtotal += $itemSubtotal;

                    $items[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $item['quantity'],
                        'price' => $product->price,
                        'subtotal' => $itemSubtotal,
                    ];

                    $product->decrement('stock', $item['quantity']);
                }

                $tax = $business->charge_tax ? ($subtotal * ($business->tax_percentage / 100)) : 0;
                $total = $subtotal + $tax;
                $paid = $request->paid;
                $change = $paid - $total;

                $ticketNumber = 'TKT-' . str_pad((Sale::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT);

                $sale = Sale::create([
                    'ticket_number' => $ticketNumber,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                    'payment_method' => $request->payment_method,
                    'paid' => $paid,
                    'change' => max(0, $change),
                    'notes' => $request->notes,
                ]);

                foreach ($items as $item) {
                    $sale->items()->create($item);
                }

                return $sale;
            });

            $sale->load('items');
            $printer = Printer::default()->first();

            if ($printer) {
                if ($printer->isNormal()) {
                    $viewName = in_array($printer->paper_width, ['a4', 'letter']) ? 'printers.ticket-a4' : 'printers.ticket-a5';
                    $ticketHtml = view($viewName, compact('sale'))->render();
                    return response()->json([
                        'success' => true,
                        'type' => 'normal',
                        'sale' => $sale,
                        'ticket_html' => $ticketHtml,
                    ]);
                } else {
                    $printerService = new ThermalPrinterService($printer);
                    $ticketData = $printerService->printTicket($sale);
                    $connectionData = $printerService->getConnectionData();
                    $ticketHtml = view('printers.ticket-a4', compact('sale'))->render();
                    
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la venta: ' . $e->getMessage()
            ], 500);
        }
    }
}
