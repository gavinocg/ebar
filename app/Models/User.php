<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\ContextoNegocio;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nombre', 'correo', 'esta_activo'])]
#[Hidden(['password', 'pin', 'remember_token'])]
class User extends Authenticatable
{
    protected $table = 'usuarios';
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'correo_verificado_en' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
            'esta_activo' => 'boolean',
        ];
    }

    public function membresias(): HasMany
    {
        return $this->hasMany(MembresiaNegocio::class, 'usuario_id');
    }

    public function rolEnNegocio(?int $negocioId): ?string
    {
        if (!$negocioId) {
            return null;
        }

        return $this->membresias()
            ->where('negocio_id', $negocioId)
            ->value('rol');
    }

    public function esAdminDelNegocioActual(): bool
    {
        return in_array($this->rolEnNegocio(app(ContextoNegocio::class)->id()), ['propietario', 'admin_bar'], true);
    }

    public function esPropietario(): bool
    {
        return $this->rolEnNegocio(app(ContextoNegocio::class)->id()) === 'propietario';
    }
}