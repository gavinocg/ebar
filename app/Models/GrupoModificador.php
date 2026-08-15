<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GrupoModificador extends Model
{
    use PerteneceANegocio;

    protected $table = 'grupos_modificadores';

    protected $fillable = [
        'negocio_id',
        'nombre',
        'requerido',
        'min_seleccion',
        'max_seleccion',
        'esta_activo',
    ];

    protected $casts = [
        'requerido' => 'boolean',
        'esta_activo' => 'boolean',
        'min_seleccion' => 'integer',
        'max_seleccion' => 'integer',
    ];

    public function modificadores(): HasMany
    {
        return $this->hasMany(Modificador::class, 'grupo_modificador_id');
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'producto_grupo_modificador');
    }
}
