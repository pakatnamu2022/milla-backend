<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApVehicleType extends Model
{
  use SoftDeletes;

  protected $table = "ap_tipo_vehiculo";

  protected $fillable = [
    'id',
    'codigo',
    'descripcion',
  ];

  const filters = [
    'search' => ['codigo', 'descripcion'],
  ];

  const sorts = [
    'id',
    'codigo',
    'descripcion',
  ];
}
