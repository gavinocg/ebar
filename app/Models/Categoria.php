<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    use PerteneceANegocio;
    protected $table = 'categorias';
    protected $fillable = ['nombre', 'descripcion', 'imagen_path', 'icono', 'color', 'orden', 'esta_activa'];

    protected $casts = [
        'orden' => 'integer',
        'esta_activa' => 'boolean',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }
}
