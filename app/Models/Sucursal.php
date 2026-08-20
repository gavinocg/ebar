<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Sucursal extends Model
{
    use PerteneceANegocio;

    protected $table = 'sucursales';

    protected $fillable = ['nombre', 'direccion', 'telefono', 'provincia', 'canton', 'ciudad', 'esta_activa'];

    protected $casts = ['esta_activa' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Sucursal $sucursal): void {
            if (!$sucursal->uuid) {
                $sucursal->uuid = (string) Str::uuid();
            }
        });
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }

    public function turnosCajero(): HasMany
    {
        return $this->hasMany(TurnoCajero::class, 'sucursal_id');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'sucursal_id');
    }

    public function impresoras(): HasMany
    {
        return $this->hasMany(Impresora::class, 'sucursal_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'sucursal_id');
    }
}
