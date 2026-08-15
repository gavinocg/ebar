<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Modificador extends Model
{
    use PerteneceANegocio;

    protected $table = 'modificadores';

    protected $fillable = [
        'negocio_id',
        'grupo_modificador_id',
        'nombre',
        'precio_extra',
        'esta_activo',
    ];

    protected $casts = [
        'precio_extra' => 'decimal:2',
        'esta_activo' => 'boolean',
    ];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(GrupoModificador::class, 'grupo_modificador_id');
    }
}
