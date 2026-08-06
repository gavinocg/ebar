<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    use PerteneceANegocio;

    protected $table = 'proveedores';

    protected $fillable = ['nombre', 'ruc', 'telefono', 'correo', 'direccion', 'esta_activo'];

    protected $casts = ['esta_activo' => 'boolean'];

    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenCompra::class, 'proveedor_id');
    }
}