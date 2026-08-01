<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TurnoCaja extends Model
{
    protected $table = 'turnos_caja';

    protected $fillable = [
        'caja_id',
        'usuario_id',
        'fondo_inicial',
        'abierto_en',
        'cerrado_en',
        'efectivo_esperado',
        'efectivo_contado',
        'diferencia',
        'estado',
        'notas',
    ];

    protected $casts = [
        'fondo_inicial' => 'decimal:2',
        'abierto_en' => 'datetime',
        'cerrado_en' => 'datetime',
        'efectivo_esperado' => 'decimal:2',
        'efectivo_contado' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'turno_caja_id');
    }

    public function movimientosEfectivo(): HasMany
    {
        return $this->hasMany(MovimientoEfectivo::class, 'turno_caja_id');
    }
}
