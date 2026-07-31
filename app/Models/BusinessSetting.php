<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = [
        'business_name',
        'logo',
        'rfc',
        'phone',
        'address',
        'ticket_message',
        'charge_tax',
        'tax_percentage',
    ];

    protected $casts = [
        'charge_tax' => 'boolean',
        'tax_percentage' => 'decimal:2',
    ];

    public static function getSettings()
    {
        return self::first() ?? new self([
            'business_name' => config('app.name', 'MI NEGOCIO'),
            'rfc' => 'XAXX010101000',
            'phone' => '(555) 123-4567',
            'address' => '',
            'ticket_message' => '¡GRACIAS POR SU COMPRA!',
            'charge_tax' => true,
            'tax_percentage' => 16.00,
        ]);
    }
}
