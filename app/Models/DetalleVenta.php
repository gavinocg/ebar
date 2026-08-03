<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleVenta extends Model
{
    use PerteneceANegocio;
    protected $table = 'detalles_venta';
    protected $fillable = ['venta_id', 'producto_id', 'nombre_producto', 'cantidad', 'precio', 'subtotal'];

    protected $casts = [
        'precio' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
