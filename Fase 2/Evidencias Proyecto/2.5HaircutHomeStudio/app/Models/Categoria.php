<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    public $timestamps = false;

    protected $table = 'categorias';

    protected $fillable = ['nombre', 'slug', 'imagen'];

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'categoria_id');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }
}
