<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ControladorAuditorias extends Controller
{
    public function index(Request $request): View
    {
        $registros = Auditoria::with('usuario')
            ->when($request->filled('modulo'), fn ($q) => $q->where('modulo', $request->input('modulo')))
            ->when($request->filled('accion'), fn ($q) => $q->where('accion', $request->input('accion')))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $modulos = Auditoria::distinct()->orderBy('modulo')->pluck('modulo');
        $acciones = Auditoria::distinct()->orderBy('accion')->pluck('accion');

        return view('auditorias.index', compact('registros', 'modulos', 'acciones'));
    }
}