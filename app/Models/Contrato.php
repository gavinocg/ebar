<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contrato extends Model
{
    protected $table = 'contratos';

    protected $fillable = [
        'negocio_id',
        'fecha_inicio',
        'fecha_fin',
        'fecha_renovacion',
        'forma_contratacion',
        'estado',
        'referencia',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_renovacion' => 'date',
    ];

    public const FORMAS = ['mensual', 'trimestral', 'semestral', 'anual', 'otro'];

    public const ESTADOS = ['activo', 'vencido', 'suspendido', 'cancelado'];

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'contrato_id');
    }

    public function totalPagado(): float
    {
        return (float) $this->pagos->where('estado', 'registrado')->sum('valor');
    }

    public function estaVigente(): bool
    {
        return $this->estado === 'activo'
            && !$this->fecha_fin->copy()->endOfDay()->isPast();
    }

    public function estaVencido(): bool
    {
        return $this->fecha_fin->copy()->endOfDay()->isPast();
    }

    public function aplicarVencimiento(): void
    {
        if (in_array($this->estado, ['suspendido', 'cancelado'], true)) {
            return;
        }

        if ($this->estaVencido()) {
            $this->estado = 'vencido';
            $this->save();
        }
    }
}