<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoEfectivo extends Model
{
    use PerteneceANegocio;
    protected $table = 'movimientos_efectivo';

    protected $fillable = [
        'negocio_id',
        'sucursal_id',
        'caja_id',
        'turno_caja_id',
        'usuario_id',
        'tipo',
        'monto',
        'motivo',
        'tipo_referencia',
        'id_referencia',
    ];

    protected $casts = ['monto' => 'decimal:2'];

    public function turnoCaja(): BelongsTo
    {
        return $this->belongsTo(TurnoCaja::class, 'turno_caja_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }
}
