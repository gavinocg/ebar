<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Impresora extends Model
{
    use PerteneceANegocio;
    protected $table = 'impresoras';
    protected $fillable = [
        'sucursal_id',
        'nombre',
        'tipo_conexion',
        'direccion',
        'puerto',
        'ancho_papel',
        'esta_activa',
        'es_predeterminada'
    ];

    protected $casts = [
        'esta_activa' => 'boolean',
        'es_predeterminada' => 'boolean',
        'puerto' => 'integer'
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function scopePredeterminada($query)
    {
        return $query->where('es_predeterminada', true)->where('esta_activa', true);
    }

    public function esTermica()
    {
        return in_array($this->tipo_conexion, ['bluetooth', 'wifi', 'lan']);
    }

    public function esConvencional()
    {
        return $this->tipo_conexion === 'normal';
    }
}
