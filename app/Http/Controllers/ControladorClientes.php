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
}
