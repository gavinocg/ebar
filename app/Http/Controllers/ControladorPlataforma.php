<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Negocio;
use Illuminate\View\View;

class ControladorPlataforma extends Controller
{
    public function index(): View
    {
        $hoy = now()->toDateString();

        return view('plataforma.index', [
            'totalNegocios' => Negocio::count(),
            'negociosActivos' => Negocio::where('esta_activo', true)->count(),
            'contratosActivos' => Contrato::where('estado', 'activo')
                ->whereDate('fecha_inicio', '<=', $hoy)
                ->whereDate('fecha_fin', '>=', $hoy)
                ->count(),
        ]);
    }
}
