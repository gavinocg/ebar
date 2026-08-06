<?php

namespace App\Http\Controllers;

use App\Models\MembresiaNegocio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ControladorAutenticacion extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'correo' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['correo' => 'Las credenciales no son válidas.'])->onlyInput('correo');
        }

        $request->session()->regenerate();
        $request->session()->put('pos_desbloqueado', true);

        $membresias = MembresiaNegocio::where('usuario_id', Auth::id())
            ->where('esta_activa', true)
            ->count();

        if (auth()->user()->rol === 'super_admin') {
            return redirect()->route('plataforma.inicio');
        }

        if ($membresias > 1) {
            return redirect()->route('negocio.seleccionar');
        }

        if ($membresias === 1) {
            return redirect()->route('punto_venta.inicio');
        }

        return redirect()->route('negocio.seleccionar');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio_sesion');
    }
}
