<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAbiertoDetalle extends Model
{
    use PerteneceANegocio;

    protected $table = 'tickets_abiertos_detalles';

    protected $fillable = [
        'negocio_id',
        'ticket_abierto_id',
        'producto_id',
        'producto_variante_id',
        'nombre_producto',
        'cantidad',
        'precio',
        'descuento',
        'subtotal',
        'modificadores',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'descuento' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'cantidad' => 'integer',
        'modificadores' => 'array',
    ];

    public function ticketAbierto(): BelongsTo
    {
        return $this->belongsTo(TicketAbierto::class, 'ticket_abierto_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
