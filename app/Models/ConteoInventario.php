<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConteoInventario extends Model
{
    use PerteneceANegocio;

    protected $table = 'conteos_inventario';

    protected $fillable = ['usuario_id', 'numero', 'fecha', 'estado', 'notas', 'aplicado_en'];

    protected $casts = [
        'fecha' => 'date',
        'aplicado_en' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleConteo::class, 'conteo_inventario_id');
    }
}