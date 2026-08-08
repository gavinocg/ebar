<?php

namespace App\Http\Controllers;

use App\Models\MembresiaNegocio;
use App\Models\Negocio;
use App\Models\Sucursal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorSeleccionNegocio extends Controller
{
    public function mostrar(Request $request): View|RedirectResponse
    {
        $membresias = $this->membresiasDelUsuario();

        if ($membresias->count() === 1) {
            $request->session()->put('negocio_id', $membresias->first()->negocio_id);

            return redirect()->route($this->destinoPorRol($membresias->first()->rol));
        }

        if ($membresias->isEmpty()) {
            abort(403, 'El usuario no tiene un negocio asignado.');
        }

        $negocios = Negocio::whereIn('id', $membresias->pluck('negocio_id'))
            ->with('sucursales')
            ->orderBy('nombre')
            ->get();

        return view('seleccion-negocio', ['negocios' => $negocios]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'negocio_id' => ['required', 'integer', Rule::in($this->membresiasDelUsuario()->pluck('negocio_id')->all())],
            'sucursal_id' => 'nullable|integer|exists:sucursales,id',
        ]);

        $request->session()->put('negocio_id', $datos['negocio_id']);

        if (!empty($datos['sucursal_id'])) {
            $sucursal = Sucursal::where('negocio_id', $datos['negocio_id'])
                ->where('id', $datos['sucursal_id'])
                ->where('esta_activa', true)
                ->first();

            if ($sucursal) {
                $request->session()->put('sucursal_id', $sucursal->id);
            }
        }

        $membresia = $this->membresiasDelUsuario()
            ->where('negocio_id', $datos['negocio_id'])
            ->first();

        return redirect()->route($this->destinoPorRol($membresia?->rol))->with('success', 'Bar seleccionado.');
    }

    public function cambiarSucursal(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'sucursal_id' => 'required|integer|exists:sucursales,id',
        ]);

        $negocioId = (int) $request->session()->get('negocio_id');

        $sucursal = Sucursal::where('negocio_id', $negocioId)
            ->where('id', $datos['sucursal_id'])
            ->where('esta_activa', true)
            ->firstOrFail();

        $request->session()->put('sucursal_id', $sucursal->id);

        $membresia = $this->membresiasDelUsuario()
            ->where('negocio_id', $negocioId)
            ->first();

        return redirect()->route($this->destinoPorRol($membresia?->rol))->with('success', 'Sucursal cambiada a ' . $sucursal->nombre);
    }

    public function cambiar(Request $request): RedirectResponse
    {
        $request->session()->forget('negocio_id');

        return redirect()->route('negocio.seleccionar');
    }

    private function destinoPorRol(?string $rol): string
    {
        return $rol === 'cajero' ? 'punto_venta.inicio' : 'panel.inicio';
    }

    private function membresiasDelUsuario()
    {
        return MembresiaNegocio::query()
            ->where('usuario_id', auth()->id())
            ->where('esta_activa', true)
            ->get();
    }
}