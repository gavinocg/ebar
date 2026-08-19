<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'contrato_id',
        'fecha_pago',
        'concepto',
        'forma_pago',
        'valor',
        'estado',
        'referencia',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'valor' => 'decimal:2',
    ];

    public const FORMAS_PAGO = ['efectivo', 'transferencia', 'tarjeta', 'otro'];

    public const ESTADOS = ['registrado', 'anulado'];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}