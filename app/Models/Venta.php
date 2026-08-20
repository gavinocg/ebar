<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venta extends Model
{
    use PerteneceANegocio;
    use SoftDeletes;

    protected $table = 'ventas';
    protected $fillable = [
        'sucursal_id',
        'numero_comprobante',
        'clave_idempotencia',
        'turno_cajero_id',
        'usuario_id',
        'cliente_id',
        'nombre_cliente',
        'descripcion_cliente',
        'entidad_financiera',
        'numero_comprobante_pago',
        'estado_cobro',
        'subtotal',
        'descuento',
        'descuento_porcentaje',
        'impuesto',
        'impuesto_habilitado',
        'porcentaje_impuesto',
        'total',
        'metodo_pago',
        'pagado',
        'cambio',
        'notas',
        'pagos_divididos',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'impuesto_habilitado' => 'boolean',
        'porcentaje_impuesto' => 'decimal:2',
        'total' => 'decimal:2',
        'pagado' => 'decimal:2',
        'cambio' => 'decimal:2',
        'pagos_divididos' => 'array',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function reembolsos(): HasMany
    {
        return $this->hasMany(Reembolso::class, 'venta_id');
    }

    public function turnoCajero(): BelongsTo
    {
        return $this->belongsTo(TurnoCajero::class, 'turno_cajero_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }
}
