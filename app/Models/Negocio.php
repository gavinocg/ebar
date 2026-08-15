<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Negocio extends Model
{
    use SoftDeletes;

    protected $table = 'negocios';

    protected $fillable = ['nombre', 'identificador', 'esta_activo', 'zona_horaria', 'moneda'];

    protected $casts = ['esta_activo' => 'boolean'];

    public function configuracion(): HasOne
    {
        return $this->hasOne(ConfiguracionNegocio::class, 'negocio_id');
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class, 'negocio_id');
    }

    public function membresia(): HasOne
    {
        return $this->hasOne(Membresia::class, 'negocio_id');
    }

    public function membresias(): HasMany
    {
        return $this->hasMany(MembresiaNegocio::class, 'negocio_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'negocio_id');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'negocio_id');
    }

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class, 'negocio_id');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'negocio_id');
    }

    public function cajas(): HasMany
    {
        return $this->hasMany(Caja::class, 'negocio_id');
    }

    public function turnosCaja(): HasMany
    {
        return $this->hasMany(TurnoCaja::class, 'negocio_id');
    }

    public function impresoras(): HasMany
    {
        return $this->hasMany(Impresora::class, 'negocio_id');
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'negocio_id');
    }

    public function movimientosEfectivo(): HasMany
    {
        return $this->hasMany(MovimientoEfectivo::class, 'negocio_id');
    }

    public function proveedores(): HasMany
    {
        return $this->hasMany(Proveedor::class, 'negocio_id');
    }

    public function ordenesCompra(): HasMany
    {
        return $this->hasMany(OrdenCompra::class, 'negocio_id');
    }

    public function conteosInventario(): HasMany
    {
        return $this->hasMany(ConteoInventario::class, 'negocio_id');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class, 'negocio_id');
    }
}
