<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntentoPin extends Model
{
    protected $table = 'pin_intentos';

    protected $fillable = ['usuario_id', 'intentos', 'bloqueado_hasta'];

    protected $casts = [
        'bloqueado_hasta' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function estaBloqueado(): bool
    {
        return $this->bloqueado_hasta !== null && now()->lt($this->bloqueado_hasta);
    }

    public function registrarFallo(): void
    {
        if ($this->bloqueado_hasta !== null && now()->gte($this->bloqueado_hasta)) {
            $this->intentos = 0;
            $this->bloqueado_hasta = null;
        }

        $nuevosIntentos = $this->intentos + 1;

        if ($nuevosIntentos >= 5) {
            $this->update([
                'intentos' => $nuevosIntentos,
                'bloqueado_hasta' => now()->addSeconds(60),
            ]);
        } else {
            $this->update(['intentos' => $nuevosIntentos]);
        }
    }

    public function resetear(): void
    {
        $this->update(['intentos' => 0, 'bloqueado_hasta' => null]);
    }
}
