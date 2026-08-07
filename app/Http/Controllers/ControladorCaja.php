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

        $membresia = MembresiaNegocio::where('negocio_id', $turno->negocio_id)
            ->where('usuario_id', $turno->usuario_id)
            ->where('rol', 'cajero')
            ->first();

        $cuadreActivo = $membresia?->cuadre_activo ?? true;

        $business = BusinessSetting::obtenerConfiguracion();
        $printer = \App\Models\Impresora::predeterminada()->first();
        $sucursales = Sucursal::where('esta_activa', true)->orderBy('nombre')->get();
        $sucursalActual = $sucursales->firstWhere('id', app(ContextoNegocio::class)->sucursalId());

        return view('pos.cierre', compact('turno', 'esperado', 'comprobantesNoEfectivo', 'business', 'printer', 'sucursales', 'sucursalActual', 'cuadreActivo'));
    }

    public function cerrar(Request $request, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $esFinal = $request->boolean('es_final');

        $turno = $this->turnoAbierto();

        if (!$turno) {
            return back()->withErrors(['caja' => 'No tienes un turno de caja abierto.']);
        }

        $membresia = MembresiaNegocio::where('negocio_id', $turno->negocio_id)
            ->where('usuario_id', $turno->usuario_id)
            ->where('rol', 'cajero')
            ->first();

        $cuadreActivo = $membresia?->cuadre_activo ?? true;
        $aprobacionActiva = $membresia?->aprobacion_activa ?? true;

        if ($esFinal && $cuadreActivo) {
            $datos = $request->validate([
                'billetes' => 'required|array',
                'billetes.*' => 'nullable|integer|min:0',
                'monedas' => 'required|array',
                'monedas.*' => 'nullable|integer|min:0',
                'notas' => 'nullable|string|max:1000',
            ]);
        } else {
            $datos = $request->validate([
                'notas' => 'nullable|string|max:1000',
            ]);
        }

        if ($esFinal && $cuadreActivo) {
            $totalBilletes = 0;
            foreach ($datos['billetes'] as $denominacion => $cantidad) {
                $totalBilletes += ((float) $denominacion) * (int) $cantidad;
            }

            $totalMonedas = 0;
            foreach ($datos['monedas'] as $denominacion => $cantidad) {
                $totalMonedas += ((float) $denominacion) * (int) $cantidad;
            }

            $contado = round($totalBilletes + $totalMonedas, 2);
        } else {
            $contado = null;
        }

        $esperado = round((float) $turno->movimientosEfectivo()->sum('monto'), 2);

        if ($esFinal) {
            $estado = $aprobacionActiva ? 'pendiente_aprobacion' : 'aprobada';
        } else {
            $estado = 'cerrada';
        }

        $turno->update([
            'cerrado_en' => $esFinal ? now() : null,
            'efectivo_esperado' => $esperado,
            'efectivo_contado' => $contado,
            'diferencia' => $contado !== null ? round($contado - $esperado, 2) : null,
            'billetes' => $esFinal && $cuadreActivo ? $datos['billetes'] : null,
            'monedas' => $esFinal && $cuadreActivo ? $datos['monedas'] : null,
            'notas' => $datos['notas'] ?? null,
            'estado' => $estado,
        ]);

        $tipoCierre = $esFinal ? 'final' : 'temporal';

        $auditoria->registrar('caja', 'cierre_turno', "Cierre {$tipoCierre} de turno #" . $turno->id, [
            'caja_id' => $turno->caja_id,
            'esperado' => $esperado,
            'contado' => $contado,
            'es_final' => $esFinal,
            'estado' => $estado,
        ], TurnoCaja::class, $turno->id);

        if ($esFinal && $aprobacionActiva) {
            $mensaje = 'Cierre registrado. Pendiente de visto bueno del administrador.';
        } elseif ($esFinal) {
            $mensaje = 'Cierre final registrado y confirmado.';
        } else {
            $mensaje = 'Cierre temporal registrado. Puedes reabrir si lo necesitas.';
        }

        return redirect()->route('punto_venta.inicio')->with('success', $mensaje);
    }

    public function cuadresPendientes(): View
    {
        $this->authorize('administrar', Caja::class);

        $negocioId = app(ContextoNegocio::class)->id();

        $pendientes = TurnoCaja::with('usuario')
            ->where('negocio_id', $negocioId)
            ->whereIn('estado', ['pendiente_aprobacion', 'pendiente_modificacion'])
            ->orderByDesc('cerrado_en')
            ->get();

        return view('caja.cuadres-pendientes', ['pendientes' => $pendientes]);
    }

    public function aprobarCuadre(TurnoCaja $turnoCaja, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $this->authorize('administrar', Caja::class);
        $negocioId = app(ContextoNegocio::class)->id();

        abort_unless($turnoCaja->negocio_id === $negocioId, 404);
        abort_unless($turnoCaja->estado === 'pendiente_aprobacion', 422, 'Este cuadre no está pendiente.');

        $turnoCaja->update([
            'estado' => 'aprobada',
            'aprobado_por' => Auth::id(),
            'aprobado_en' => now(),
        ]);

        $auditoria->registrar('caja', 'aprobar_cuadre', 'Cuadre aprobado del turno #' . $turnoCaja->id, [
            'turno_caja_id' => $turnoCaja->id,
            'esperado' => $turnoCaja->efectivo_esperado,
            'contado' => $turnoCaja->efectivo_contado,
            'diferencia' => $turnoCaja->diferencia,
        ], TurnoCaja::class, $turnoCaja->id);

        return back()->with('success', 'Cuadre aprobado correctamente.');
    }

    public function rechazarCuadre(TurnoCaja $turnoCaja, Request $request, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $this->authorize('administrar', Caja::class);
        $negocioId = app(ContextoNegocio::class)->id();

        abort_unless($turnoCaja->negocio_id === $negocioId, 404);
        abort_unless($turnoCaja->estado === 'pendiente_aprobacion', 422, 'Este cuadre no está pendiente.');

        $turnoCaja->update([
            'estado' => 'abierta',
            'cerrado_en' => null,
            'efectivo_contado' => null,
            'diferencia' => null,
            'notas' => trim(($turnoCaja->notas ? $turnoCaja->notas . ' | ' : '') . 'Cuadre rechazado: ' . $request->input('motivo', '')),
        ]);

        $auditoria->registrar('caja', 'rechazar_cuadre', 'Cuadre rechazado del turno #' . $turnoCaja->id, [
            'turno_caja_id' => $turnoCaja->id,
            'motivo' => $request->input('motivo'),
        ], TurnoCaja::class, $turnoCaja->id);

        return back()->with('success', 'Cuadre rechazado. El cajero puede realizar un nuevo cierre.');
    }

    public function solicitarModificacion(TurnoCaja $turnoCaja, Request $request): RedirectResponse
    {
        $negocioId = app(ContextoNegocio::class)->id();

        abort_unless($turnoCaja->negocio_id === $negocioId, 404);
        abort_unless(in_array($turnoCaja->estado, ['aprobada', 'cerrada'], true), 422, 'Este cuadre no puede modificarse.');
        abort_unless($turnoCaja->usuario_id === Auth::id(), 403, 'Solo el cajero del turno puede solicitar modificación.');

        $turnoCaja->update([
            'notas' => trim(($turnoCaja->notas ? $turnoCaja->notas . ' | ' : '') . 'Solicitud de modificación: ' . $request->input('motivo', '')),
            'estado' => 'pendiente_modificacion',
        ]);

        return back()->with('success', 'Solicitud de modificación enviada al administrador.');
    }

    public function autorizarModificacion(TurnoCaja $turnoCaja, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $this->authorize('administrar', Caja::class);
        $negocioId = app(ContextoNegocio::class)->id();

        abort_unless($turnoCaja->negocio_id === $negocioId, 404);
        abort_unless($turnoCaja->estado === 'pendiente_modificacion', 422, 'No hay solicitud de modificación pendiente.');

        $turnoCaja->update([
            'estado' => 'abierta',
            'cerrado_en' => null,
            'efectivo_contado' => null,
            'diferencia' => null,
            'aprobado_por' => null,
            'aprobado_en' => null,
        ]);

        $auditoria->registrar('caja', 'autorizar_modificacion', 'Modificación autorizada del turno #' . $turnoCaja->id, [
            'turno_caja_id' => $turnoCaja->id,
        ], TurnoCaja::class, $turnoCaja->id);

        return back()->with('success', 'Modificación autorizada. El cajero puede realizar un nuevo cierre.');
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
