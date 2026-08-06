<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reembolso extends Model
{
    use PerteneceANegocio;

    protected $table = 'reembolsos';

    protected $fillable = [
        'negocio_id',
        'sucursal_id',
        'venta_id',
        'usuario_id',
        'tipo',
        'monto',
        'motivo',
        'metodo',
        'autorizado_por',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(ReembolsoDetalle::class, 'reembolso_id');
    }
}