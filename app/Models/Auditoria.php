<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    use PerteneceANegocio;

    protected $table = 'auditorias';

    protected $fillable = [
        'usuario_id',
        'modulo',
        'accion',
        'tipo_referencia',
        'id_referencia',
        'descripcion',
        'detalles',
        'direccion_ip',
    ];

    protected $casts = [
        'detalles' => 'array',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}