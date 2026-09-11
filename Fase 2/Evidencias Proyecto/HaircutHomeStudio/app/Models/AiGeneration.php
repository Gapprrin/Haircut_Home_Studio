<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneration extends Model
{
    protected $fillable = [
        'usuario_id',
        'reserva_id',
        'servicio_id',
        'preset',
        'input_path',
        'output_path',
        'input_hash',
        'status',
        'provider_request_id',
        'error_code',
        'ip_hash',
        'completed_at',
        'expires_at',
    ];

    protected $hidden = [
        'input_path',
        'output_path',
        'input_hash',
        'ip_hash',
        'provider_request_id',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function estaLista(): bool
    {
        return $this->status === 'completed' && $this->output_path !== null;
    }
}
