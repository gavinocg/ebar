<?php

namespace App\Http\Controllers;

use App\Models\Producto as Product;
use App\Models\Categoria as Category;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ControladorProductos extends Controller
{
    public function index()
    {
        $this->authorize('gestionar', Product::class);

        $products = Product::with('categoria', 'sucursal')->orderBy('nombre')->paginate(15);
        return view('products.index', compact('products'));
    }

    public function create()
    {
        $this->authorize('gestionar', Product::class);

        $categories = Category::orderBy('nombre')->get();
        $sucursales = Sucursal::orderBy('nombre')->get();
        return view('products.create', compact('categories', 'sucursales'));
    }

    public function store(Request $request)
    {
        $this->authorize('gestionar', Product::class);

        $request->validate([
            'sucursal_id' => 'nullable|integer|exists:sucursales,id',
            'categoria_id' => 'required|exists:categorias,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'imagen' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:2048|dimensions:max_width=2000,max_height=2000',
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'distintivo' => 'nullable|string|max:40',
            'distintivo_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'destacado' => 'nullable|boolean',
            'precio' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0|max:100',
            'existencias' => 'required_if:maneja_existencias,1|nullable|integer|min:0',
            'nivel_minimo' => 'nullable|integer|min:0',
            'maneja_existencias' => 'nullable|boolean',
            'codigo_barras' => 'nullable|string|unique:productos,codigo_barras',
        ]);

        $datos = $request->only([
            'sucursal_id', 'categoria_id', 'nombre', 'descripcion', 'precio', 'descuento', 'existencias', 'nivel_minimo', 'codigo_barras', 'maneja_existencias',
            'color', 'distintivo', 'distintivo_color',
        ]);
        $datos['esta_activo'] = $request->boolean('esta_activo');
        $datos['maneja_existencias'] = $request->boolean('maneja_existencias');
        $datos['existencias'] = $datos['maneja_existencias'] ? ($datos['existencias'] ?? 0) : 0;
        $datos['destacado'] = $request->boolean('destacado');
        if ($request->hasFile('imagen')) {
            $datos['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }
        Product::create($datos);

        return redirect()->route('productos.index')->with('success', 'Producto creado');
    }

    public function edit(Product $product)
    {
        $this->authorize('gestionar', $product);

        $categories = Category::orderBy('nombre')->get();
        $sucursales = Sucursal::orderBy('nombre')->get();
        return view('products.edit', compact('product', 'categories', 'sucursales'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('gestionar', $product);

        $request->validate([
            'sucursal_id' => 'nullable|integer|exists:sucursales,id',
            'categoria_id' => 'required|exists:categorias,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'imagen' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:2048|dimensions:max_width=2000,max_height=2000',
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'distintivo' => 'nullable|string|max:40',
            'distintivo_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'destacado' => 'nullable|boolean',
            'precio' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0|max:100',
            'existencias' => 'required_if:maneja_existencias,1|nullable|integer|min:0',
            'nivel_minimo' => 'nullable|integer|min:0',
            'maneja_existencias' => 'nullable|boolean',
            'codigo_barras' => 'nullable|string|unique:productos,codigo_barras,' . $product->id,
        ]);

        $datos = $request->only([
            'sucursal_id', 'categoria_id', 'nombre', 'descripcion', 'precio', 'descuento', 'existencias', 'nivel_minimo', 'codigo_barras', 'maneja_existencias',
            'color', 'distintivo', 'distintivo_color',
        ]);
        $datos['esta_activo'] = $request->boolean('esta_activo');
        $datos['maneja_existencias'] = $request->boolean('maneja_existencias');
        $datos['existencias'] = $datos['maneja_existencias'] ? ($datos['existencias'] ?? 0) : 0;
        $datos['destacado'] = $request->boolean('destacado');
        if ($request->hasFile('imagen')) {
            if ($product->imagen_path) {
                Storage::disk('public')->delete($product->imagen_path);
            }
            $datos['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }
        $product->update($datos);

        return redirect()->route('productos.index')->with('success', 'Producto actualizado');
    }

    public function destroy(Product $product)
    {
        $this->authorize('gestionar', $product);

        if ($product->imagen_path) {
            Storage::disk('public')->delete($product->imagen_path);
        }
        $product->delete();
        return redirect()->route('productos.index')->with('success', 'Producto eliminado');
    }

    public function exportar(): StreamedResponse
    {
        $this->authorize('gestionar', Product::class);

        $productos = Product::with('categoria', 'sucursal')->orderBy('nombre')->get();

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['nombre', 'categoria', 'precio', 'existencias', 'nivel_minimo', 'codigo_barras', 'sucursal', 'maneja_existencias']);

        foreach ($productos as $producto) {
            fputcsv($csv, [
                $producto->nombre,
                $producto->categoria?->nombre ?? '',
                $producto->precio,
                $producto->existencias,
                $producto->nivel_minimo ?? '',
                $producto->codigo_barras ?? '',
                $producto->sucursal?->nombre ?? '',
                $producto->maneja_existencias ? '1' : '0',
            ]);
        }

        rewind($csv);

        return response()->streamDownload(function () use ($csv) {
            stream_get_contents($csv);
            rewind($csv);
            fpassthru($csv);
        }, 'productos-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importar(Request $request)
    {
        $this->authorize('gestionar', Product::class);

        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $archivo = fopen($request->file('archivo')->getRealPath(), 'r');
        $encabezados = fgetcsv($archivo);
        $categorias = Category::pluck('id', 'nombre');
        $sucursales = Sucursal::pluck('id', 'nombre');

        $creados = 0;
        $actualizados = 0;
        $filas = [];

        while (($fila = fgetcsv($archivo)) !== false) {
            $datos = array_combine($encabezados, $fila);

            if (blank($datos['nombre'] ?? null)) {
                continue;
            }

            $filas[] = [
                'nombre' => trim($datos['nombre']),
                'categoria_id' => $categorias->get(trim($datos['categoria'] ?? '')),
                'precio' => (float) ($datos['precio'] ?? 0),
                'descuento' => (float) ($datos['descuento'] ?? 0),
                'existencias' => (int) ($datos['existencias'] ?? 0),
                'nivel_minimo' => (int) ($datos['nivel_minimo'] ?? 0) ?: null,
                'codigo_barras' => $datos['codigo_barras'] ?? null,
                'sucursal_id' => $sucursales->get(trim($datos['sucursal'] ?? '')) ?: null,
                'maneja_existencias' => (bool) ($datos['maneja_existencias'] ?? true),
            ];
        }

        fclose($archivo);

        return DB::transaction(function () use ($filas, &$creados, &$actualizados) {
            foreach ($filas as $fila) {
                $consulta = Product::query();

                if (!blank($fila['codigo_barras'])) {
                    $consulta->where('codigo_barras', $fila['codigo_barras']);
                } else {
                    $consulta->where('nombre', $fila['nombre']);
                }

                $producto = $consulta->first();

                if ($producto) {
                    $producto->update(array_filter($fila, fn ($v, $k) => in_array($k, $producto->getFillable(), true) && !blank($v), ARRAY_FILTER_USE_BOTH));
                    $actualizados++;
                } else {
                    Product::create($fila);
                    $creados++;
                }
            }

            return back()->with('success', "Importación completada: {$creados} creados, {$actualizados} actualizados.");
        });
    }
}
