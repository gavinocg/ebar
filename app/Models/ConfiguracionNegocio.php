<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use App\Services\ContextoNegocio;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionNegocio extends Model
{
    use PerteneceANegocio;
    protected $table = 'configuraciones_negocio';
    protected $fillable = [
        'nombre_negocio',
        'logotipo',
        'rfc',
        'telefono',
        'direccion',
        'mensaje_comprobante',
        'cobrar_impuesto',
        'descuento_activo',
        'porcentaje_impuesto',
    ];

    protected $casts = [
        'cobrar_impuesto' => 'boolean',
        'descuento_activo' => 'boolean',
        'porcentaje_impuesto' => 'decimal:2',
    ];

    public static function obtenerConfiguracion()
    {
        $negocioId = app(ContextoNegocio::class)->id();

        if ($negocioId !== null) {
            return self::where('negocio_id', $negocioId)->first() ?? new self([
                'nombre_negocio' => config('app.name', 'MI NEGOCIO'),
                'rfc' => 'XAXX010101000',
                'telefono' => '(02) 000-0000',
                'direccion' => '',
                'mensaje_comprobante' => '¡GRACIAS POR SU COMPRA!',
                'cobrar_impuesto' => true,
                'descuento_activo' => false,
                'porcentaje_impuesto' => 15.00,
            ]);
        }

        return new self([
            'nombre_negocio' => config('app.name', 'MI NEGOCIO'),
            'rfc' => 'XAXX010101000',
            'telefono' => '(02) 000-0000',
            'direccion' => '',
            'mensaje_comprobante' => '¡GRACIAS POR SU COMPRA!',
            'cobrar_impuesto' => true,
            'descuento_activo' => false,
            'porcentaje_impuesto' => 15.00,
        ]);
    }
}
