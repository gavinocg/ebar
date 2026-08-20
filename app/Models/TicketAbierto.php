<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAbierto extends Model
{
    use PerteneceANegocio;

    protected $table = 'tickets_abiertos';

    protected $fillable = [
        'negocio_id',
        'sucursal_id',
        'turno_cajero_id',
        'usuario_id',
        'nombre',
        'descripcion',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(TicketAbiertoDetalle::class, 'ticket_abierto_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function turnoCajero(): BelongsTo
    {
        return $this->belongsTo(TurnoCajero::class, 'turno_cajero_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->detalles->sum('subtotal');
    }

    public function getTotalDescuentoAttribute(): float
    {
        return (float) $this->detalles->sum('descuento');
    }
}
