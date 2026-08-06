<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionNegocio;
use App\Models\Membresia;
use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Plan;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorNegocios extends Controller
{
    public function index(): View
    {
        $negocios = Negocio::with('sucursales', 'membresia.plan')->orderBy('nombre')->get();

        return view('plataforma.negocios.index', compact('negocios'));
    }

    public function create(): View
    {
        return view('plataforma.negocios.create', [
            'planes' => Plan::where('esta_activo', true)->orderBy('precio_mensual')->get(),
            'zonasHorarias' => $this->zonasHorarias(),
            'monedas' => ['USD' => 'USD - Dólar estadounidense'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'identificador' => ['required', 'string', 'max:60', 'alpha_dash', 'unique:negocios,identificador'],
            'zona_horaria' => 'required|string|max:60',
            'moneda' => 'required|string|size:3',
            'plan_id' => ['required', Rule::exists('planes', 'id')],
            'nombre_admin' => 'required|string|max:255',
            'correo_admin' => ['required', 'email', 'unique:usuarios,correo'],
            'clave_admin' => 'required|string|min:8|confirmed',
            'nombre_sucursal' => 'nullable|string|max:255',
        ]);

        $negocio = Negocio::create([
            'nombre' => $datos['nombre'],
            'identificador' => $datos['identificador'],
            'esta_activo' => true,
            'zona_horaria' => $datos['zona_horaria'],
            'moneda' => $datos['moneda'],
        ]);

        Sucursal::create([
            'negocio_id' => $negocio->id,
            'nombre' => $datos['nombre_sucursal'] ?: 'Sucursal principal',
            'esta_activa' => true,
        ]);

        $configuracion = new ConfiguracionNegocio(['nombre_negocio' => $negocio->nombre]);
        $configuracion->negocio_id = $negocio->id;
        $configuracion->save();

        Membresia::create([
            'negocio_id' => $negocio->id,
            'plan_id' => $datos['plan_id'],
            'estado' => 'prueba',
            'fecha_inicio' => now(),
            'fecha_vencimiento' => now()->addDays(30),
        ]);

        $admin = User::create([
            'nombre' => $datos['nombre_admin'],
            'correo' => $datos['correo_admin'],
            'password' => $datos['clave_admin'],
            'rol' => 'admin_bar',
        ]);

        MembresiaNegocio::create([
            'negocio_id' => $negocio->id,
            'usuario_id' => $admin->id,
            'rol' => 'admin_bar',
            'esta_activa' => true,
        ]);

        return redirect()->route('plataforma.negocios.index')->with('success', "Bar {$negocio->nombre} creado.");
    }

    public function edit(Negocio $negocio): View
    {
        return view('plataforma.negocios.edit', [
            'negocio' => $negocio,
            'planes' => Plan::all(),
            'zonasHorarias' => $this->zonasHorarias(),
            'monedas' => ['USD' => 'USD - Dólar estadounidense'],
        ]);
    }

    public function update(Request $request, Negocio $negocio): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'identificador' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('negocios', 'identificador')->ignore($negocio->id)],
            'zona_horaria' => 'required|string|max:60',
            'moneda' => 'required|string|size:3',
            'esta_activo' => 'nullable|boolean',
            'plan_id' => ['required', Rule::exists('planes', 'id')],
        ]);

        $negocio->update([
            'nombre' => $datos['nombre'],
            'identificador' => $datos['identificador'],
            'zona_horaria' => $datos['zona_horaria'],
            'moneda' => $datos['moneda'],
            'esta_activo' => $request->boolean('esta_activo'),
        ]);

        $negocio->membresia()->update(['plan_id' => $datos['plan_id']]);

        return redirect()->route('plataforma.negocios.index')->with('success', 'Bar actualizado.');
    }

    public function destroy(Negocio $negocio): RedirectResponse
    {
        $negocio->delete();

        return redirect()->route('plataforma.negocios.index')->with('success', 'Bar eliminado.');
    }

    private function zonasHorarias(): array
    {
        return ['America/Guayaquil' => 'America/Guayaquil'];
    }
}