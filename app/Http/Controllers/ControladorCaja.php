<?php

namespace App\Http\Controllers;

use App\Models\TurnoCajero;
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
use Illuminate\View\View;

class ControladorCaja extends Controller
{
    public function abrir(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'fondo_inicial' => 'required|numeric|min:0|max:9999999999.99',
        ]);

        $negocioId = app(ContextoNegocio::class)->id();

        $membresiaCajero = MembresiaNegocio::where('negocio_id', $negocioId)
            ->where('usuario_id', Auth::id())
            ->where('rol', 'cajero')
            ->first();

        abort_unless($membresiaCajero?->sucursal_id, 422, 'Debes tener una sucursal asignada para abrir tu turno.');

        return DB::transaction(function () use ($datos, $negocioId, $membresiaCajero) {
            $turnoEnSucursalCajero = $membresiaCajero->sucursal_id;

            $turnoExistente = TurnoCajero::where('usuario_id', Auth::id())
                ->where('estado', 'abierta')
                ->first();

            if ($turnoExistente) {
                return back()->withErrors(['caja' => 'Ya tienes un turno de cajero abierto.']);
            }

            $turno = TurnoCajero::create([
                'negocio_id' => $negocioId,
                'sucursal_id' => $turnoEnSucursalCajero,
                'usuario_id' => Auth::id(),
                'fondo_inicial' => $datos['fondo_inicial'],
                'abierto_en' => now(),
                'estado' => 'abierta',
            ]);

            MovimientoEfectivo::create([
                'negocio_id' => $negocioId,
                'sucursal_id' => $turnoEnSucursalCajero,
                'turno_cajero_id' => $turno->id,
                'usuario_id' => Auth::id(),
                'tipo' => 'fondo_inicial',
                'monto' => $datos['fondo_inicial'],
                'motivo' => 'Apertura de turno',
            ]);

            return redirect()->route('punto_venta.inicio')->with('success', 'Turno abierto correctamente.');
        });
    }

    public function cerrarForm()
    {
        $turno = $this->turnoAbierto();

        if (!$turno) {
            return redirect()->route('punto_venta.inicio')->withErrors(['caja' => 'No tienes un turno de cajero abierto.']);
        }

        $turno->load('movimientosEfectivo');

        $esperado = $this->efectivoEsperado($turno);

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
            return back()->withErrors(['caja' => 'No tienes un turno de cajero abierto.']);
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

        return DB::transaction(function () use ($turno, $esFinal, $cuadreActivo, $aprobacionActiva, $datos, $auditoria) {
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

            $esperado = $this->efectivoEsperado($turno);

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

            $auditoria->registrar('turno', 'cierre_turno', "Cierre {$tipoCierre} de turno #" . $turno->id, [
                'esperado' => $esperado,
                'contado' => $contado,
                'es_final' => $esFinal,
                'estado' => $estado,
            ], TurnoCajero::class, $turno->id);

            if ($esFinal && $aprobacionActiva) {
                $mensaje = 'Cierre registrado. Pendiente de visto bueno del administrador.';
            } elseif ($esFinal) {
                $mensaje = 'Cierre final registrado y confirmado.';
            } else {
                $mensaje = 'Cierre temporal registrado. Puedes reabrir si lo necesitas.';
            }

            return redirect()->route('punto_venta.inicio')->with('success', $mensaje);
        });
    }

    public function cuadresPendientes(): View
    {
        $this->authorize('aprobarCuadres', TurnoCajero::class);

        $negocioId = app(ContextoNegocio::class)->id();

        $pendientes = TurnoCajero::with('usuario')
            ->where('negocio_id', $negocioId)
            ->whereIn('estado', ['pendiente_aprobacion', 'pendiente_modificacion'])
            ->orderByDesc('cerrado_en')
            ->get();

        return view('caja.cuadres-pendientes', ['pendientes' => $pendientes]);
    }

    public function aprobarCuadre(TurnoCajero $turnoCajero, Request $request, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $this->authorize('aprobarCuadres', TurnoCajero::class);
        $negocioId = app(ContextoNegocio::class)->id();

        abort_unless($turnoCajero->negocio_id === $negocioId, 404);
        abort_unless($turnoCajero->estado === 'pendiente_aprobacion', 422, 'Este cuadre no está pendiente.');

        $diferencia = (float) ($turnoCajero->diferencia ?? 0);
        if (abs($diferencia) > 1) {
            $request->validate(['motivo' => 'required|string|max:500']);
        }

        $turnoCajero->update([
            'estado' => 'aprobada',
            'aprobado_por' => Auth::id(),
            'aprobado_en' => now(),
        ]);

        $auditoria->registrar('turno', 'aprobar_cuadre', 'Cuadre aprobado del turno #' . $turnoCajero->id, [
            'turno_cajero_id' => $turnoCajero->id,
            'esperado' => $turnoCajero->efectivo_esperado,
            'contado' => $turnoCajero->efectivo_contado,
            'diferencia' => $turnoCajero->diferencia,
            'motivo' => $request->input('motivo'),
        ], TurnoCajero::class, $turnoCajero->id);

        return back()->with('success', 'Cuadre aprobado correctamente.');
    }

    public function rechazarCuadre(TurnoCajero $turnoCajero, Request $request, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $this->authorize('aprobarCuadres', TurnoCajero::class);
        $negocioId = app(ContextoNegocio::class)->id();

        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        abort_unless($turnoCajero->negocio_id === $negocioId, 404);
        abort_unless($turnoCajero->estado === 'pendiente_aprobacion', 422, 'Este cuadre no está pendiente.');

        $turnoCajero->update([
            'estado' => 'abierta',
            'cerrado_en' => null,
            'efectivo_contado' => null,
            'diferencia' => null,
            'notas' => trim(($turnoCajero->notas ? $turnoCajero->notas . ' | ' : '') . 'Cuadre rechazado: ' . $request->input('motivo', '')),
        ]);

        $auditoria->registrar('turno', 'rechazar_cuadre', 'Cuadre rechazado del turno #' . $turnoCajero->id, [
            'turno_cajero_id' => $turnoCajero->id,
            'motivo' => $request->input('motivo'),
        ], TurnoCajero::class, $turnoCajero->id);

        return back()->with('success', 'Cuadre rechazado. El cajero puede realizar un nuevo cierre.');
    }

    public function solicitarModificacion(TurnoCajero $turnoCajero, Request $request): RedirectResponse
    {
        $negocioId = app(ContextoNegocio::class)->id();

        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        return DB::transaction(function () use ($turnoCajero, $negocioId, $request) {
            $turno = TurnoCajero::whereKey($turnoCajero->id)->lockForUpdate()->firstOrFail();

            abort_unless($turno->negocio_id === $negocioId, 404);
            abort_unless(in_array($turno->estado, ['aprobada', 'cerrada'], true), 422, 'Este cuadre no puede modificarse.');
            abort_unless($turno->usuario_id === Auth::id(), 403, 'Solo el cajero del turno puede solicitar modificación.');

            $turno->update([
                'notas' => trim(($turno->notas ? $turno->notas . ' | ' : '') . 'Solicitud de modificación: ' . $request->input('motivo', '')),
                'estado' => 'pendiente_modificacion',
            ]);

            return back()->with('success', 'Solicitud de modificación enviada al administrador.');
        });
    }

    public function autorizarModificacion(TurnoCajero $turnoCajero, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $this->authorize('aprobarCuadres', TurnoCajero::class);
        $negocioId = app(ContextoNegocio::class)->id();

        abort_unless($turnoCajero->negocio_id === $negocioId, 404);
        abort_unless($turnoCajero->estado === 'pendiente_modificacion', 422, 'No hay solicitud de modificación pendiente.');

        $turnoCajero->update([
            'estado' => 'abierta',
            'cerrado_en' => null,
            'efectivo_contado' => null,
            'diferencia' => null,
            'aprobado_por' => null,
            'aprobado_en' => null,
        ]);

        $auditoria->registrar('turno', 'autorizar_modificacion', 'Modificación autorizada del turno #' . $turnoCajero->id, [
            'turno_cajero_id' => $turnoCajero->id,
        ], TurnoCajero::class, $turnoCajero->id);

        return back()->with('success', 'Modificación autorizada. El cajero puede realizar un nuevo cierre.');
    }

    public function movimiento(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => 'required|in:entrada,retiro,gasto',
            'monto' => 'required|numeric|min:0.01|max:9999999999.99',
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
            'negocio_id' => $turno->negocio_id,
            'sucursal_id' => $turno->sucursal_id,
            'turno_cajero_id' => $turno->id,
            'usuario_id' => Auth::id(),
            'tipo' => $datos['tipo'],
            'monto' => $monto,
            'motivo' => $datos['motivo'],
        ]);

        return back()->with('success', 'Movimiento de efectivo registrado.');
    }

    public function reabrir(TurnoCajero $turnoCajero, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $this->authorize('reabrir', TurnoCajero::class);
        $negocioId = app(ContextoNegocio::class)->id();

        abort_unless($turnoCajero->negocio_id === $negocioId, 404);
        abort_unless($turnoCajero->estado === 'cerrada', 422, 'Solo se pueden reabrir turnos cerrados.');

        $detalles = [
            'esperado' => $turnoCajero->efectivo_esperado,
            'contado' => $turnoCajero->efectivo_contado,
        ];

        $turnoCajero->update([
            'estado' => 'abierta',
            'cerrado_en' => null,
            'efectivo_esperado' => null,
            'efectivo_contado' => null,
            'diferencia' => null,
            'notas' => trim(($turnoCajero->notas ? $turnoCajero->notas . ' | ' : '') . 'Reabierto por ' . Auth::user()->nombre),
        ]);

        $auditoria->registrar('turno', 'reapertura_turno', 'Reapertura de turno #' . $turnoCajero->id, $detalles, TurnoCajero::class, $turnoCajero->id);

        return back()->with('success', 'Turno reabierto correctamente.');
    }

    public function reporte(Request $request)
    {
        $this->authorize('verArqueos', TurnoCajero::class);

        $negocioId = app(ContextoNegocio::class)->id();

        $turnos = TurnoCajero::with('usuario', 'sucursal')
            ->when($request->filled('usuario_id'), fn ($q) => $q->where('usuario_id', $request->input('usuario_id')))
            ->when($request->filled('sucursal_id'), fn ($q) => $q->where('sucursal_id', $request->input('sucursal_id')))
            ->orderByDesc('abierto_en')
            ->get();

        $usuarios = MembresiaNegocio::with('usuario')
            ->where('negocio_id', $negocioId)
            ->get()
            ->pluck('usuario')
            ->filter();

        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('caja.arqueos', [
            'turnos' => $turnos,
            'usuarios' => $usuarios,
            'sucursales' => $sucursales,
            'usuarioSeleccionado' => $request->input('usuario_id'),
            'sucursalSeleccionada' => $request->input('sucursal_id'),
        ]);
    }

    public function turnoDetalle(TurnoCajero $turnoCajero)
    {
        $this->authorize('verArqueos', TurnoCajero::class);

        $negocioId = app(ContextoNegocio::class)->id();
        abort_unless($turnoCajero->negocio_id === $negocioId, 404);

        $turnoCajero->load('usuario', 'sucursal', 'ventas', 'movimientosEfectivo');

        $ventasEfectivo = $turnoCajero->movimientosEfectivo()
            ->where('tipo', 'venta')
            ->sum('monto');
        $entradas = $turnoCajero->movimientosEfectivo()
            ->whereIn('tipo', ['entrada', 'fondo_inicial'])
            ->sum('monto');
        $salidas = $turnoCajero->movimientosEfectivo()
            ->whereIn('tipo', ['retiro', 'gasto'])
            ->sum('monto');

        return view('caja.turno-detalle', [
            'turno' => $turnoCajero,
            'ventasEfectivo' => $ventasEfectivo,
            'entradas' => $entradas,
            'salidas' => abs($salidas),
        ]);
    }

    private function turnoAbierto(): ?TurnoCajero
    {
        return TurnoCajero::where('usuario_id', Auth::id())
            ->where('estado', 'abierta')
            ->latest('id')
            ->first();
    }

    private function efectivoEsperado(TurnoCajero $turno): float
    {
        return round((float) $turno->movimientosEfectivo()->where('tipo', '!=', 'transferencia')->sum('monto'), 2);
    }
}