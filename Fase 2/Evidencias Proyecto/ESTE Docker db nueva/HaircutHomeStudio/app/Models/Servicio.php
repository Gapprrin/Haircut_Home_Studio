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

    protected $primaryKey = 'id_servicio';

    protected $table = 'SERVICIO';

    protected $fillable = ['nombre_servicio', 'descripcion', 'imagen', 'duracion_min', 'precio', 'disponibilidad', 'id_tipo_servicio'];

    protected function casts(): array
    {
        return [
            'disponibilidad' => 'boolean',
            'duracion_min' => 'integer',
            'precio' => 'decimal:2',
        ];
    }

    public function scopeSimulables(Builder $query): Builder
    {
        return $query->where('disponibilidad', true)
            ->where('nombre_servicio', '<>', 'Lavado')
            ->whereHas('tipoServicio', fn (Builder $tipoServicio) => $tipoServicio->whereIn('slug', ['corte', 'color']));
    }

    public function precioFormateado(): string
    {
        return ($this->precio ? 'Desde ' : '').Format::precio($this->precio);
    }

    public function tipoServicio(): BelongsTo
    {
        return $this->belongsTo(TipoServicio::class, 'id_tipo_servicio');
    }

    public function detalleReservas(): HasMany
    {
        return $this->hasMany(DetalleReserva::class, 'id_servicio');
    }
}
