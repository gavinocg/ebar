<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use PerteneceANegocio;
    use SoftDeletes;

    protected $table = 'productos';
    protected $fillable = [
        'sucursal_id',
        'categoria_id',
        'nombre',
        'descripcion',
        'imagen_path',
        'color',
        'distintivo',
        'distintivo_color',
        'destacado',
        'precio',
        'descuento',
        'existencias',
        'nivel_minimo',
        'maneja_existencias',
        'codigo_barras',
        'esta_activo',
    ];

    protected $casts = [
        'esta_activo' => 'boolean',
        'precio' => 'decimal:2',
        'descuento' => 'decimal:2',
        'destacado' => 'boolean',
        'maneja_existencias' => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'producto_id');
    }

    public function gruposModificadores(): BelongsToMany
    {
        return $this->belongsToMany(GrupoModificador::class, 'producto_grupo_modificador');
    }

    public function variantes(): HasMany
    {
        return $this->hasMany(ProductoVariante::class, 'producto_id');
    }
}
