<?php

namespace App\Http\Controllers;

use App\Models\MembresiaNegocio;
use App\Models\User;
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

    public function cajero(): View
    {
        return view('auth.cajero');
    }

    public function cajeroBuscar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'correo' => 'required|email',
        ]);

        $usuario = User::where('correo', $datos['correo'])->where('esta_activo', true)->first();

        if (!$usuario || blank($usuario->pin)) {
            return back()->withErrors(['correo' => 'Credenciales no válidas.'])->onlyInput('correo');
        }

        $request->session()->put('cajero_pin_id', $usuario->id);

        return redirect()->route('inicio_sesion.pin');
    }

    public function pin(): View
    {
        $usuario = User::find(session('cajero_pin_id'));

        if (!$usuario) {
            return redirect()->route('inicio_sesion.cajero');
        }

        return view('auth.pin', ['usuario' => $usuario]);
    }

    public function pinValidar(Request $request): RedirectResponse
    {
        $usuario = User::find(session('cajero_pin_id'));

        if (!$usuario) {
            return redirect()->route('inicio_sesion.cajero');
        }

        $intento = \App\Models\IntentoPin::firstOrCreate(['usuario_id' => $usuario->id]);

        if ($intento->estaBloqueado()) {
            $segundos = ceil($intento->bloqueado_hasta->diffInSeconds(now()));
            return redirect()->back()->withErrors(['pin' => "Demasiados intentos. Espera {$segundos} segundos."]);
        }

        $datos = $request->validate([
            'pin' => 'required|digits:4',
        ]);

        if (!password_verify($datos['pin'], $usuario->pin)) {
            $intento->registrarFallo();
            return redirect()->back()->withErrors(['pin' => 'PIN incorrecto.']);
        }

        $intento->resetear();
        $request->session()->forget('cajero_pin_id');
        $request->session()->regenerate();
        Auth::login($usuario);
        $request->session()->put('pos_desbloqueado', true);

        $membresiaCajero = MembresiaNegocio::where('usuario_id', $usuario->id)
            ->where('rol', 'cajero')
            ->where('esta_activa', true)
            ->first();

        if ($membresiaCajero && $membresiaCajero->sucursal_id) {
            $request->session()->put('sucursal_id', $membresiaCajero->sucursal_id);
        }

        $membresias = MembresiaNegocio::where('usuario_id', $usuario->id)
            ->where('esta_activa', true)
            ->count();

        if ($membresias > 1) {
            return redirect()->route('negocio.seleccionar');
        }

        return redirect()->route('punto_venta.inicio');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio_sesion');
    }
}
