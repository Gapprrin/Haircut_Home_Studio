<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiaOff extends Model
{
    public $timestamps = false;

    protected $table = 'dias_off';

    protected $fillable = ['configuracion_id', 'fecha'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function configuracion(): BelongsTo
    {
        return $this->belongsTo(Configuracion::class, 'configuracion_id');
    }
}
