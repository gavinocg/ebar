<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionNegocio extends Model
{
    protected $table = 'configuraciones_negocio';
    protected $fillable = [
        'nombre_negocio',
        'logotipo',
        'rfc',
        'telefono',
        'direccion',
        'mensaje_comprobante',
        'cobrar_impuesto',
        'porcentaje_impuesto',
    ];

    protected $casts = [
        'cobrar_impuesto' => 'boolean',
        'porcentaje_impuesto' => 'decimal:2',
    ];

    public static function obtenerConfiguracion()
    {
        return self::first() ?? new self([
            'nombre_negocio' => config('app.name', 'MI NEGOCIO'),
            'rfc' => 'XAXX010101000',
            'telefono' => '(02) 000-0000',
            'direccion' => '',
            'mensaje_comprobante' => '¡GRACIAS POR SU COMPRA!',
            'cobrar_impuesto' => true,
            'porcentaje_impuesto' => 15.00,
        ]);
    }
}
