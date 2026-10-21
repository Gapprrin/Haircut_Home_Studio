<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoServicio extends Model
{
    public $timestamps = false;

    protected $table = 'TIPO_SERVICIO';

    protected $primaryKey = 'id_tipo_servicio';

    protected $fillable = ['tipo_servicio', 'imagen'];

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_tipo_servicio');
    }
}
