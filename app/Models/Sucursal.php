<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    use PerteneceANegocio;

    protected $table = 'sucursales';

    protected $fillable = ['nombre', 'direccion', 'telefono', 'esta_activa'];

    protected $casts = ['esta_activa' => 'boolean'];

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }

    public function cajas(): HasMany
    {
        return $this->hasMany(Caja::class, 'sucursal_id');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'sucursal_id');
    }

    public function impresoras(): HasMany
    {
        return $this->hasMany(Impresora::class, 'sucursal_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'sucursal_id');
    }

    public function turnosCaja(): HasMany
    {
        return $this->hasMany(TurnoCaja::class, 'sucursal_id');
    }
}
