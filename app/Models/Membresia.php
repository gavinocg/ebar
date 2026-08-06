<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membresia extends Model
{
    protected $table = 'membresias';

    protected $fillable = [
        'negocio_id',
        'plan_id',
        'estado',
        'fecha_inicio',
        'fecha_vencimiento',
        'fecha_renovacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_renovacion' => 'date',
    ];

    public const ESTADOS = ['prueba', 'activa', 'suspendida', 'vencida', 'cancelada'];

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function estaVigente(): bool
    {
        return in_array($this->estado, ['prueba', 'activa'], true)
            && !$this->fecha_vencimiento->isPast();
    }

    public function estaVencida(): bool
    {
        return $this->fecha_vencimiento->isPast();
    }

    public function aplicarVencimiento(): void
    {
        if ($this->estado === 'suspendida' || $this->estado === 'cancelada') {
            return;
        }

        if ($this->estaVencida()) {
            $this->estado = 'vencida';
            $this->save();
        }
    }
}