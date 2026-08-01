<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    protected $table = 'cajas';

    protected $fillable = ['nombre', 'esta_activa'];

    protected $casts = ['esta_activa' => 'boolean'];

    public function turnos(): HasMany
    {
        return $this->hasMany(TurnoCaja::class, 'caja_id');
    }
}
