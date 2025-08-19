<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApEngineType extends BaseModel
{
  use SoftDeletes;

  protected $table = 'ap_tipo_motores_vehiculo';

  protected $fillable = [
    'id',
    'codigo',
    'descripcion',
  ];

  const filters = [
    'search' => ['codigo', 'descripcion'],
    'codigo' => 'like',
    'descripcion' => 'like',
  ];

  const sorts = [
    'id',
    'codigo',
    'descripcion',
  ];

}
