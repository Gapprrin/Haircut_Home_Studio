<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'email',
        'password',
        'rol',
        'es_invitado',
        'origen',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'es_invitado' => 'boolean',
        ];
    }

    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function esPeluquero(): bool
    {
        return $this->rol === 'peluquero';
    }

    public function esStaff(): bool
    {
        return $this->esAdmin() || $this->esPeluquero();
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'usuario_id');
    }
}
