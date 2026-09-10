<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MesVisible extends Model
{
    public $timestamps = false;

    protected $table = 'meses_visibles';

    protected $fillable = ['configuracion_id', 'anio', 'mes'];

    public function configuracion(): BelongsTo
    {
        return $this->belongsTo(Configuracion::class, 'configuracion_id');
    }
}
