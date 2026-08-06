<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleConteo extends Model
{
    protected $table = 'detalles_conteo';

    protected $fillable = ['conteo_inventario_id', 'producto_id', 'existencias_sistema', 'existencias_reales', 'diferencia'];

    protected $casts = [
        'existencias_sistema' => 'integer',
        'existencias_reales' => 'integer',
        'diferencia' => 'integer',
    ];

    public function conteo(): BelongsTo
    {
        return $this->belongsTo(ConteoInventario::class, 'conteo_inventario_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}