<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApVehicleStatus extends Model
{
  use SoftDeletes;

  protected $table = "ap_estados_vehiculos";

  protected $fillable = [
    'id',
    'codigo',
    'descripcion',
    'uso',
    'color',
  ];

  const filters = [
    'search' => ['codigo', 'descripcion'],
    'uso' => 'like',
  ];

  const sorts = [
    'id',
    'codigo',
    'descripcion',
  ];

  public function setCodigoAttribute($value)
  {
    $this->attributes['codigo'] = Str::upper(Str::ascii($value));
  }

  public function setDescripcionAttribute($value)
  {
    $this->attributes['descripcion'] = Str::upper(Str::ascii($value));
  }
}
