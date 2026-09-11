<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reserva extends Model
{
    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = null;

    protected $table = 'reservas';

    protected $fillable = ['usuario_id', 'servicio_id', 'fecha', 'hora', 'foto', 'lugar', 'estado'];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'creado_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function generacionesIa(): HasMany
    {
        return $this->hasMany(AiGeneration::class, 'reserva_id');
    }

    public function puedeCancelar(): bool
    {
        if (! in_array($this->estado, ['pendiente', 'confirmada'], true)) {
            return false;
        }

        return $this->fecha->setTimeFromTimeString($this->hora)->greaterThanOrEqualTo(now()->addMinutes(10));
    }
}
