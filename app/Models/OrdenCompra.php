<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenCompra extends Model
{
    use PerteneceANegocio;

    protected $table = 'ordenes_compra';

    protected $fillable = [
        'proveedor_id',
        'usuario_id',
        'numero',
        'fecha',
        'estado',
        'subtotal',
        'impuesto',
        'total',
        'notas',
        'recibida_en',
    ];

    protected $casts = [
        'fecha' => 'date',
        'recibida_en' => 'datetime',
        'subtotal' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleOrdenCompra::class, 'orden_compra_id');
    }
}