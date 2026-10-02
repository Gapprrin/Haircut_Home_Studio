<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    public $timestamps = false;

    protected $table = 'servicios';

    protected $fillable = ['categoria_id', 'nombre', 'descripcion', 'imagen', 'duracion_min', 'precio', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'duracion_min' => 'integer',
            'precio' => 'decimal:2',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'servicio_id');
    }
}
