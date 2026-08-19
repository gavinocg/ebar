<?php

namespace App\Http\Controllers;

use App\Models\MembresiaNegocio;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $usuario = User::where('correo', $credentials['correo'])->first();

        if (!$usuario || !$usuario->esta_activo || !Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['correo' => 'Las credenciales no son válidas.'])->onlyInput('correo');
        }

        $request->session()->regenerate();

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
            $membresia = MembresiaNegocio::where('usuario_id', Auth::id())
                ->where('esta_activa', true)
                ->first();

            if ($membresia && $membresia->rol === 'cajero') {
                return redirect()->route('punto_venta.inicio');
            }

            return redirect()->route('panel.inicio');
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

    public function pin(): View|RedirectResponse
    {
        $usuario = User::find(session('cajero_pin_id'));

        if (!$usuario || !$usuario->esta_activo) {
            return redirect()->route('inicio_sesion.cajero');
        }

        return view('auth.pin', ['usuario' => $usuario]);
    }

    public function pinValidar(Request $request): RedirectResponse
    {
        $usuario = User::find(session('cajero_pin_id'));

        if (!$usuario || !$usuario->esta_activo) {
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

        if ($membresiaCajero) {
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

    public function cambiarPassword(): View
    {
        return view('auth.cambiar-password');
    }

    public function guardarPassword(Request $request): RedirectResponse
    {
        if (!Auth::validate([
            'correo' => Auth::user()->correo,
            'password' => $request->input('password_actual'),
        ])) {
            return back()->withErrors(['password_actual' => 'La contraseña actual no es correcta.']);
        }

        $datos = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $usuario = Auth::user();
        $usuario->password = $datos['password'];
        $usuario->debe_cambiar_password = false;
        $usuario->remember_token = null;
        $usuario->save();

        DB::table('sessions')
            ->where('user_id', $usuario->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        $request->session()->regenerate();

        return redirect()->route($this->destinoDespuesDelCambio($request, $usuario));
    }

    private function destinoDespuesDelCambio(Request $request, User $usuario): string
    {
        if ($usuario->rol === 'super_admin') {
            return 'plataforma.inicio';
        }

        $membresias = MembresiaNegocio::where('usuario_id', $usuario->id)
            ->where('esta_activa', true)
            ->get();

        if ($membresias->count() > 1) {
            return 'negocio.seleccionar';
        }

        $membresia = $membresias->first();

        if (!$membresia) {
            return 'negocio.seleccionar';
        }

        app(\App\Services\ContextoNegocio::class)->establecer($membresia->negocio_id);
        $request->session()->put('negocio_id', $membresia->negocio_id);

        return $membresia->rol === 'cajero' ? 'punto_venta.inicio' : 'panel.inicio';
    }
}
