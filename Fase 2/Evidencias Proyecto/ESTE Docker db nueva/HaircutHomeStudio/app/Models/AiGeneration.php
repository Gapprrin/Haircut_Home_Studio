<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGeneration extends Model
{
    protected $table = 'AI_GENERATIONS';

    protected $primaryKey = 'id_ai_gen';

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'style_label',
        'style_category',
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

    public function estaLista(): bool
    {
        return $this->status === 'completed' && $this->output_path !== null;
    }
}
