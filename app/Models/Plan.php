<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $table = 'planes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio_mensual',
        'duracion_dias',
        'limite_cajeros',
        'limite_cajas',
        'limite_sucursales',
        'esta_activo',
    ];

    protected $casts = [
        'precio_mensual' => 'decimal:2',
        'duracion_dias' => 'integer',
        'limite_cajeros' => 'integer',
        'limite_cajas' => 'integer',
        'limite_sucursales' => 'integer',
        'esta_activo' => 'boolean',
    ];

    public function membresias(): HasMany
    {
        return $this->hasMany(Membresia::class, 'plan_id');
    }
}