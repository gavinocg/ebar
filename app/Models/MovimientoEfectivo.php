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
        'turno_cajero_id',
        'usuario_id',
        'tipo',
        'monto',
        'motivo',
        'tipo_referencia',
        'id_referencia',
    ];

    protected $casts = ['monto' => 'decimal:2'];

    public function turnoCajero(): BelongsTo
    {
        return $this->belongsTo(TurnoCajero::class, 'turno_cajero_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }
}