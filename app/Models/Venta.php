<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venta extends Model
{
    protected $table = 'ventas';
    protected $fillable = [
        'numero_comprobante',
        'clave_idempotencia',
        'turno_caja_id',
        'usuario_id',
        'subtotal',
        'impuesto',
        'impuesto_habilitado',
        'porcentaje_impuesto',
        'total',
        'metodo_pago',
        'pagado',
        'cambio',
        'notas',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'impuesto_habilitado' => 'boolean',
        'porcentaje_impuesto' => 'decimal:2',
        'total' => 'decimal:2',
        'pagado' => 'decimal:2',
        'cambio' => 'decimal:2',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function turnoCaja(): BelongsTo
    {
        return $this->belongsTo(TurnoCaja::class, 'turno_caja_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
