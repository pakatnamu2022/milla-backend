<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApVehicleCategory extends BaseModel
{
  use SoftDeletes;

  protected $table = 'ap_categoria_vehiculos';

  protected $fillable = [
    'id',
    'name',
    'status',
  ];

  const filters = [
    'search' => ['name'],
    'status' => '='
  ];

  const sorts = [
    'name',
  ];

  public function setNameAttribute($value)
  {
    $this->attributes['name'] = Str::upper(Str::ascii($value));
  }
}
