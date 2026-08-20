<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'roles';

    protected $fillable = ['negocio_id', 'nombre', 'slug', 'descripcion', 'es_sistema', 'esta_activo'];

    protected $casts = ['es_sistema' => 'boolean', 'esta_activo' => 'boolean'];

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'rol_permiso', 'rol_id', 'permiso_id');
    }

    public function membresias(): HasMany
    {
        return $this->hasMany(MembresiaNegocio::class, 'rol_id');
    }

    public function tienePermiso(string $clave): bool
    {
        return $this->permisos->contains('clave', $clave);
    }

    public function scopePorNegocio($query, ?int $negocioId)
    {
        return $query->where(fn ($q) => $q->where('negocio_id', $negocioId)->orWhereNull('negocio_id'));
    }
}
