<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionNegocio as BusinessSetting;
use App\Services\RegistradorAuditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ControladorConfiguracionNegocio extends Controller
{
    public function index()
    {
        $this->authorize('administrar', BusinessSetting::class);

        $settings = BusinessSetting::first();
        return view('settings.business', compact('settings'));
    }

    public function update(Request $request, RegistradorAuditoria $auditoria)
    {
        $this->authorize('administrar', BusinessSetting::class);

        $request->validate([
            'nombre_negocio' => 'required|string|max:255',
            'logotipo' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'rfc' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
            'mensaje_comprobante' => 'nullable|string',
            'cobrar_impuesto' => 'nullable|boolean',
            'descuento_activo' => 'nullable|boolean',
            'porcentaje_impuesto' => 'nullable|numeric|min:0|max:100',
        ]);

        $settings = BusinessSetting::firstOrNew();
        
        $settings->nombre_negocio = $request->nombre_negocio;
        $settings->rfc = $request->rfc;
        $settings->telefono = $request->telefono;
        $settings->direccion = $request->direccion;
        $settings->mensaje_comprobante = $request->mensaje_comprobante;
        $settings->cobrar_impuesto = $request->boolean('cobrar_impuesto');
        $settings->descuento_activo = $request->boolean('descuento_activo');
        $settings->porcentaje_impuesto = $request->porcentaje_impuesto ?? 15.00;

        if ($request->hasFile('logotipo')) {
            if ($settings->logotipo) {
                Storage::disk('public')->delete($settings->logotipo);
            }
            $path = $request->file('logotipo')->store('logotipos', 'public');
            $settings->logotipo = $path;
        }

        $settings->save();

        $auditoria->registrar('configuracion', 'actualizar', 'Configuración del negocio actualizada', [
            'cobrar_impuesto' => $settings->cobrar_impuesto,
            'porcentaje_impuesto' => $settings->porcentaje_impuesto,
        ], BusinessSetting::class, $settings->id);

        return redirect()->route('configuracion.negocio')->with('success', 'Configuración actualizada');
    }
}
