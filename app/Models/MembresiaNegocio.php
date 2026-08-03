<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembresiaNegocio extends Model
{
    protected $table = 'membresias_negocio';

    protected $fillable = ['negocio_id', 'usuario_id', 'rol', 'esta_activa'];

    protected $casts = ['esta_activa' => 'boolean'];

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
