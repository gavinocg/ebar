<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Impresora extends Model
{
    protected $table = 'impresoras';
    protected $fillable = [
        'nombre',
        'tipo_conexion',
        'tipo_impresora',
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
