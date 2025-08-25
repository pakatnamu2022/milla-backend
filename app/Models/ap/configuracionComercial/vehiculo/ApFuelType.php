<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApFuelType extends Model
{
  use SoftDeletes;

  protected $table = 'ap_tipo_combustible';

  protected $fillable = [
    'id',
    'codigo',
    'descripcion',
    'motor_electrico',
    'status',
  ];

  const filters = [
    'search' => ['codigo', 'descripcion'],
    'status' => '=',
  ];

  const sorts = [
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
