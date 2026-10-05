<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    public $timestamps = false;

    protected $table = 'productos';

    protected $fillable = ['categoria_id', 'nombre', 'descripcion', 'precio', 'imagen', 'activo', 'orden'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'precio' => 'decimal:2',
            'orden' => 'integer',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
