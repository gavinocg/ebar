<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoVariante extends Model
{
    use PerteneceANegocio;

    protected $table = 'producto_variantes';

    protected $fillable = [
        'negocio_id',
        'producto_id',
        'nombre',
        'precio',
        'sku',
        'esta_activo',
        'stock',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'esta_activo' => 'boolean',
        'stock' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
