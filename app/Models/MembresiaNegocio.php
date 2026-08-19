<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembresiaNegocio extends Model
{
    protected $table = 'membresias_negocio';

    protected $fillable = ['negocio_id', 'usuario_id', 'rol', 'rol_id', 'sucursal_id', 'esta_activa', 'cuadre_activo', 'aprobacion_activa', 'limite_cajeros'];

    protected $casts = ['esta_activa' => 'boolean', 'cuadre_activo' => 'boolean', 'aprobacion_activa' => 'boolean', 'limite_cajeros' => 'integer'];

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function rolAsignado(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }
}
