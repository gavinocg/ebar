<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sucursal extends Model
{
    use PerteneceANegocio;

    protected $table = 'sucursales';

    protected $fillable = ['negocio_id', 'nombre', 'direccion', 'telefono', 'esta_activa'];

    protected $casts = ['esta_activa' => 'boolean'];

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }
}
