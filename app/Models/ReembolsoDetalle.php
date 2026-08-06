<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReembolsoDetalle extends Model
{
    protected $table = 'reembolsos_detalles';

    protected $fillable = [
        'reembolso_id',
        'detalle_venta_id',
        'cantidad',
        'monto',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'monto' => 'decimal:2',
    ];

    public function reembolso(): BelongsTo
    {
        return $this->belongsTo(Reembolso::class, 'reembolso_id');
    }

    public function detalleVenta(): BelongsTo
    {
        return $this->belongsTo(DetalleVenta::class, 'detalle_venta_id');
    }
}