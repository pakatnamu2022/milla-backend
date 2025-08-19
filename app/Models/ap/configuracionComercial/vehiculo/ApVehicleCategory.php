<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApVehicleCategory extends BaseModel
{
  use SoftDeletes;

  protected $table = 'ap_categoria_vehiculos';

  protected $fillable = [
    'id',
    'name',
  ];

  const filters = [
    'name' => 'like',
  ];

  const sorts = [
    'id',
    'name',
  ];
}
