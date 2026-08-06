<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Negocio extends Model
{
    protected $table = 'negocios';

    protected $fillable = ['nombre', 'identificador', 'esta_activo', 'zona_horaria', 'moneda'];

    protected $casts = ['esta_activo' => 'boolean'];

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class, 'negocio_id');
    }

    public function membresia(): HasOne
    {
        return $this->hasOne(Membresia::class, 'negocio_id');
    }

    public function membresias(): HasMany
    {
        return $this->hasMany(MembresiaNegocio::class, 'negocio_id');
    }
}
