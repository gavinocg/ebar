<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Rol;
use App\Services\ContextoNegocio;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[Fillable(['nombre', 'correo', 'esta_activo', 'cedula', 'celular', 'debe_cambiar_password'])]
#[Hidden(['password', 'pin', 'remember_token'])]
class User extends Authenticatable
{
    protected $table = 'usuarios';
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $usuario): void {
            if (!$usuario->uuid) {
                $usuario->uuid = (string) Str::uuid();
            }
        });
    }

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
            'debe_cambiar_password' => 'boolean',
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

    public function membresiaEnNegocio(?int $negocioId): ?MembresiaNegocio
    {
        if (!$negocioId) {
            return null;
        }

        return $this->membresias()
            ->where('negocio_id', $negocioId)
            ->first();
    }

    public function tienePermiso(string $clave): bool
    {
        $negocioId = app(ContextoNegocio::class)->id();
        $membresia = $this->membresiaEnNegocio($negocioId);

        if (!$membresia) {
            return false;
        }

        if ($membresia->rol_id) {
            return (bool) ($membresia->rolAsignado?->tienePermiso($clave));
        }

        if ($membresia->rol === 'propietario') {
            return true;
        }

        $rol = Rol::query()
            ->where('slug', $membresia->rol)
            ->whereNull('negocio_id')
            ->first();

        return (bool) ($rol?->tienePermiso($clave));
    }

    public function permisosEnNegocio(?int $negocioId = null): Collection
    {
        $negocioId = $negocioId ?? app(ContextoNegocio::class)->id();
        $membresia = $this->membresiaEnNegocio($negocioId);

        if (!$membresia) {
            return collect();
        }

        if ($membresia->rol_id) {
            return $membresia->rolAsignado?->permisos ?? collect();
        }

        return Rol::query()
            ->where('slug', $membresia->rol)
            ->whereNull('negocio_id')
            ->first()?->permisos ?? collect();
    }
}