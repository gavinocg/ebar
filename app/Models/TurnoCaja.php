<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TurnoCaja extends Model
{
    use PerteneceANegocio;
    use SoftDeletes;

    protected $table = 'turnos_caja';

    protected $fillable = [
        'sucursal_id',
        'caja_id',
        'usuario_id',
        'fondo_inicial',
        'abierto_en',
        'cerrado_en',
        'efectivo_esperado',
        'efectivo_contado',
        'diferencia',
        'billetes',
        'monedas',
        'aprobado_por',
        'aprobado_en',
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
        'billetes' => 'array',
        'monedas' => 'array',
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

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }
}
