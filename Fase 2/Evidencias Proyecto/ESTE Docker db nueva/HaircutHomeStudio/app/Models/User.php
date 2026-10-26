<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    /** Indica que esta clase hereda de la clase User base de Laravel **/
    use HasFactory, Notifiable;

    protected $table = 'USUARIO';

    protected $primaryKey = 'id_usuario';

    /** Estas le indican al modelo que dichas columnas son timestamps y las debe gestionar **/
    public const CREATED_AT = 'fecha_creacion';

    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'contrasenia',
        'nombre_usuario',
        'id_rol',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'contrasenia'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contrasenia' => 'hashed'
        ];
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'id_usuario');
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    public function getAuthPasswordName(): string
    {
        return 'contrasenia';
    }

    protected function nombre(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->nombre_usuario);
    }

    public function nombreRol(): string
    {
        return strtolower((string) ($this->rol?->rol ?? ''));
    }

    public function esAdmin(): bool
    {
        return $this->nombreRol() === 'admin';
    }

    public function esPeluquero(): bool
    {
        return $this->nombreRol() === 'peluquero';
    }

    public function esStaff(): bool
    {
        return in_array($this->nombreRol(), ['admin', 'peluquero'], true);
    }

    /** El esquema actual no tiene esta columna; se mantiene para no romper cuotas/AI. */
    protected function aiSinLimite(): Attribute
    {
        return Attribute::get(fn (): bool => false);
    }
}

