<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorClientes extends Controller
{
    public function buscar(Request $request): JsonResponse
    {
        $texto = trim($request->string('q')->toString());

        if (mb_strlen($texto) < 2) {
            return response()->json([]);
        }

        $clientes = Cliente::where('esta_activo', true)
            ->where('nombre', 'like', "%{$texto}%")
            ->orderBy('nombre')
            ->limit(8)
            ->get(['id', 'nombre', 'descripcion']);

        return response()->json($clientes);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $cliente = Cliente::create([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'esta_activo' => true,
        ]);

        return response()->json([
            'success' => true,
            'cliente' => $cliente->only(['id', 'nombre', 'descripcion']),
        ]);
    }
}
