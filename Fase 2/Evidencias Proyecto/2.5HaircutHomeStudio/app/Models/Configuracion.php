<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Configuracion extends Model
{
    public $timestamps = false;

    protected $table = 'configuracion';

    protected $fillable = ['hora_inicio', 'hora_fin', 'dias_atencion'];

    public function diasOff(): HasMany
    {
        return $this->hasMany(DiaOff::class, 'configuracion_id');
    }

    public function mesesVisibles(): HasMany
    {
        return $this->hasMany(MesVisible::class, 'configuracion_id');
    }
}
