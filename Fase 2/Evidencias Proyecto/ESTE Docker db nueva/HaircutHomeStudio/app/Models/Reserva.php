<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reserva extends Model
{
    public const CREATED_AT = 'fecha_creacion_reserva';

    public const UPDATED_AT = null;

    protected $primaryKey = 'id_reserva';

    protected $table = 'RESERVA';

    protected $fillable = ['hora', 'fecha', 'foto', 'imagen_simulada', 'id_usuario', 'id_estado_reserva', 'id_tipo_reserva'];

    protected $hidden = ['imagen_simulada'];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'fecha_creacion_reserva' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function estadoReserva(): BelongsTo
    {
        return $this->belongsTo(EstadoReserva::class, 'id_estado_reserva');
    }

    public function tipoReserva(): BelongsTo
    {
        return $this->belongsTo(TipoReserva::class, 'id_tipo_reserva');
    }

    public function detallesReserva(): HasMany
    {
        return $this->hasMany(DetalleReserva::class, 'id_reserva');
    }

    public function puedeCancelar(): bool
    {
        if (! in_array($this->estadoReserva?->estado_reserva, ['En aprovación', 'Agendada'], true)
            || $this->fecha === null
            || $this->hora === null) {
            return false;
        }

        return $this->fecha->copy()
            ->setTimeFromTimeString($this->hora)
            ->greaterThanOrEqualTo(now()->addMinutes(10));
    }
}
