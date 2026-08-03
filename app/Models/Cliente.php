<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use PerteneceANegocio;
    protected $table = 'clientes';

    protected $fillable = ['nombre', 'descripcion', 'esta_activo'];

    protected $casts = ['esta_activo' => 'boolean'];

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }
}
