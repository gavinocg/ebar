<?php

namespace App\Http\Controllers;

use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use Illuminate\View\View;

class ControladorPlataforma extends Controller
{
    public function index(): View
    {
        return view('plataforma.index', [
            'totalNegocios' => Negocio::count(),
            'negociosActivos' => Negocio::where('esta_activo', true)->count(),
            'membresiasActivas' => MembresiaNegocio::where('esta_activa', true)->count(),
        ]);
    }
}
