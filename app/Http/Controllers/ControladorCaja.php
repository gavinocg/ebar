<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\TurnoCaja;
use App\Models\MovimientoEfectivo;
use App\Models\MembresiaNegocio;
use App\Models\Sucursal;
use App\Services\ContextoNegocio;
use App\Services\RegistradorAuditoria;
use App\Models\ConfiguracionNegocio as BusinessSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ControladorCaja extends Controller
{
    public function abrir(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'fondo_inicial' => 'required|numeric|min:0|max:99999999.99',
            'caja_id' => 'nullable|integer|exists:cajas,id',
        ]);

        if ($this->turnoAbierto()) {
            return back()->withErrors(['caja' => 'Ya tienes un turno de caja abierto.']);
        }

        $caja = Caja::where('esta_activa', true)
            ->when($request->filled('caja_id'), fn ($q) => $q->where('id', $datos['caja_id']))
            ->orderBy('id')
            ->firstOrFail();

        TurnoCaja::create([
            'sucursal_id' => $caja->sucursal_id,
            'caja_id' => $caja->id,
            'usuario_id' => Auth::id(),
            'fondo_inicial' => $datos['fondo_inicial'],
            'abierto_en' => now(),
            'estado' => 'abierta',
        ]);

        $turno = $this->turnoAbierto();
        MovimientoEfectivo::create([
            'caja_id' => $caja->id,
            'turno_caja_id' => $turno->id,
            'usuario_id' => Auth::id(),
            'tipo' => 'fondo_inicial',
            'monto' => $datos['fondo_inicial'],
            'motivo' => 'Apertura de caja',
        ]);

        return back()->with('success', 'Turno de caja abierto correctamente.');
    }

    public function cerrarForm()
    {
        $turno = $this->turnoAbierto();

        if (!$turno) {
            return redirect()->route('punto_venta.inicio')->withErrors(['caja' => 'No tienes un turno de caja abierto.']);
        }

        $turno->load('movimientosEfectivo');

        $esperado = round((float) $turno->movimientosEfectivo()->sum('monto'), 2);

        $comprobantesNoEfectivo = $turno->ventas()
            ->whereIn('metodo_pago', ['credito', 'transferencia'])
            ->orderBy('created_at', 'desc')
            ->get();

        $business = BusinessSetting::obtenerConfiguracion();

        return view('pos.cierre', compact('turno', 'esperado', 'comprobantesNoEfectivo', 'business'));
    }

    public function cerrar(Request $request, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $datos = $request->validate([
            'billetes' => 'required|array',
            'billetes.*' => 'nullable|integer|min:0',
            'monedas' => 'required|array',
            'monedas.*' => 'nullable|integer|min:0',
            'notas' => 'nullable|string|max:1000',
        ]);

        $turno = $this->turnoAbierto();

        if (!$turno) {
            return back()->withErrors(['caja' => 'No tienes un turno de caja abierto.']);
        }

        $totalBilletes = 0;
        foreach ($datos['billetes'] as $denominacion => $cantidad) {
            $totalBilletes += ((float) $denominacion) * (int) $cantidad;
        }

        $totalMonedas = 0;
        foreach ($datos['monedas'] as $denominacion => $cantidad) {
            $totalMonedas += ((float) $denominacion) * (int) $cantidad;
        }

        $contado = round($totalBilletes + $totalMonedas, 2);
        $esperado = round((float) $turno->movimientosEfectivo()->sum('monto'), 2);

        $turno->update([
            'cerrado_en' => now(),
            'efectivo_esperado' => $esperado,
            'efectivo_contado' => $contado,
            'diferencia' => round($contado - $esperado, 2),
            'billetes' => $datos['billetes'],
            'monedas' => $datos['monedas'],
            'notas' => $datos['notas'] ?? null,
            'estado' => 'cerrada',
        ]);

        $auditoria->registrar('caja', 'cierre_turno', 'Cierre de turno #' . $turno->id, [
            'caja_id' => $turno->caja_id,
            'esperado' => $esperado,
            'contado' => $contado,
            'diferencia' => round($contado - $esperado, 2),
        ], TurnoCaja::class, $turno->id);

        return redirect()->route('punto_venta.inicio')->with('success', 'Turno de caja cerrado correctamente.');
    }

    public function movimiento(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => 'required|in:entrada,retiro,gasto',
            'monto' => 'required|numeric|min:0.01|max:99999999.99',
            'motivo' => 'required|string|max:255',
        ]);
        $turno = $this->turnoAbierto();

        if (!$turno) {
            return back()->withErrors(['caja' => 'Debes abrir un turno antes de registrar efectivo.']);
        }

        $monto = in_array($datos['tipo'], ['retiro', 'gasto'], true)
            ? -abs((float) $datos['monto'])
            : abs((float) $datos['monto']);

        MovimientoEfectivo::create([
            'caja_id' => $turno->caja_id,
            'turno_caja_id' => $turno->id,
            'usuario_id' => Auth::id(),
            'tipo' => $datos['tipo'],
            'monto' => $monto,
            'motivo' => $datos['motivo'],
        ]);

        return back()->with('success', 'Movimiento de efectivo registrado.');
    }

    public function reabrir(TurnoCaja $turnoCaja, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $this->authorize('administrar', Caja::class);

        abort_unless($turnoCaja->estado === 'cerrada', 422, 'Solo se pueden reabrir turnos cerrados.');

        $detalles = [
            'caja_id' => $turnoCaja->caja_id,
            'esperado' => $turnoCaja->efectivo_esperado,
            'contado' => $turnoCaja->efectivo_contado,
        ];

        $turnoCaja->update([
            'estado' => 'abierta',
            'cerrado_en' => null,
            'efectivo_esperado' => null,
            'efectivo_contado' => null,
            'diferencia' => null,
            'notas' => trim(($turnoCaja->notas ? $turnoCaja->notas . ' | ' : '') . 'Reabierto por ' . Auth::user()->nombre),
        ]);

        $auditoria->registrar('caja', 'reapertura_turno', 'Reapertura de turno #' . $turnoCaja->id, $detalles, TurnoCaja::class, $turnoCaja->id);

        return back()->with('success', 'Turno reabierto correctamente.');
    }

    public function reporte(Request $request)
    {
        $this->authorize('administrar', Caja::class);

        $negocioId = app(ContextoNegocio::class)->id();

        $turnos = TurnoCaja::with('usuario', 'caja.sucursal')
            ->when($request->filled('usuario_id'), fn ($q) => $q->where('usuario_id', $request->input('usuario_id')))
            ->when($request->filled('caja_id'), fn ($q) => $q->where('caja_id', $request->input('caja_id')))
            ->when($request->filled('sucursal_id'), fn ($q) => $q->where('sucursal_id', $request->input('sucursal_id')))
            ->orderByDesc('abierto_en')
            ->get();

        $usuarios = MembresiaNegocio::with('usuario')
            ->where('negocio_id', $negocioId)
            ->get()
            ->pluck('usuario')
            ->filter();

        $cajas = Caja::orderBy('nombre')->get();
        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('caja.arqueos', [
            'turnos' => $turnos,
            'usuarios' => $usuarios,
            'cajas' => $cajas,
            'sucursales' => $sucursales,
            'usuarioSeleccionado' => $request->input('usuario_id'),
            'cajaSeleccionada' => $request->input('caja_id'),
            'sucursalSeleccionada' => $request->input('sucursal_id'),
        ]);
    }

    public function turnoDetalle(TurnoCaja $turnoCaja)
    {
        $this->authorize('administrar', Caja::class);

        $turnoCaja->load('usuario', 'caja.sucursal', 'ventas', 'movimientosEfectivo');

        $ventasEfectivo = $turnoCaja->movimientosEfectivo()
            ->where('tipo', 'venta')
            ->sum('monto');
        $entradas = $turnoCaja->movimientosEfectivo()
            ->whereIn('tipo', ['entrada', 'fondo_inicial'])
            ->sum('monto');
        $salidas = $turnoCaja->movimientosEfectivo()
            ->whereIn('tipo', ['retiro', 'gasto'])
            ->sum('monto');

        return view('caja.turno-detalle', [
            'turno' => $turnoCaja,
            'ventasEfectivo' => $ventasEfectivo,
            'entradas' => $entradas,
            'salidas' => abs($salidas),
        ]);
    }

    private function turnoAbierto(): ?TurnoCaja
    {
        return TurnoCaja::where('usuario_id', Auth::id())
            ->where('estado', 'abierta')
            ->latest('id')
            ->first();
    }
}
