<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'id_producto';

    protected $table = 'PRODUCTO';

    protected $fillable = ['producto', 'descripcion', 'precio', 'imagen', 'disponibilidad', 'id_categoria'];

    protected function casts(): array
    {
        return [
            'disponibilidad' => 'boolean',
            'precio' => 'decimal:2'
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }
}
