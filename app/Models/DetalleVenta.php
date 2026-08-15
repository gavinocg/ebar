<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DetalleVenta extends Model
{
    use PerteneceANegocio;
    protected $table = 'detalles_venta';
    protected $fillable = ['venta_id', 'producto_id', 'producto_variante_id', 'nombre_producto', 'cantidad', 'precio', 'descuento', 'subtotal'];

    protected $casts = [
        'precio' => 'decimal:2',
        'descuento' => 'decimal:2',
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

    public function variante(): BelongsTo
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }

    public function modificadores(): BelongsToMany
    {
        return $this->belongsToMany(Modificador::class, 'detalle_venta_modificadores', 'detalle_venta_id', 'modificador_id')
            ->withPivot('precio_extra');
    }
}
