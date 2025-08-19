<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApVehicleCategory extends Model
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
}
