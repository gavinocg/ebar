<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $table = 'productos';
    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
        'imagen_path',
        'color',
        'distintivo',
        'distintivo_color',
        'destacado',
        'precio',
        'existencias',
        'codigo_barras',
        'esta_activo',
    ];

    protected $casts = [
        'esta_activo' => 'boolean',
        'precio' => 'decimal:2',
        'destacado' => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'producto_id');
    }
}
