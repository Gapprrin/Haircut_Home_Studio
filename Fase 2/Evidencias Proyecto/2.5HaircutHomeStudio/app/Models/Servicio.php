<?php

namespace App\Models;

use App\Support\Format;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    public $timestamps = false;

    protected $table = 'servicios';

    protected $fillable = ['categoria_id', 'nombre', 'descripcion', 'imagen', 'duracion_min', 'precio', 'precio_desde', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'duracion_min' => 'integer',
            'precio' => 'decimal:2',
            'precio_desde' => 'boolean',
        ];
    }

    public function scopeSimulables(Builder $query): Builder
    {
        return $query->where('activo', true)
            ->where('nombre', '<>', 'Lavado')
            ->whereHas('categoria', fn (Builder $category) => $category->whereIn('slug', ['corte', 'color']));
    }

    public function precioFormateado(): string
    {
        return ($this->precio_desde ? 'Desde ' : '').Format::precio($this->precio);
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
