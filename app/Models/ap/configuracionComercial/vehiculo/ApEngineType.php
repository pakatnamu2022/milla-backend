<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApEngineType extends BaseModel
{
  use SoftDeletes;

  protected $table = 'ap_tipo_motores_vehiculo';

  protected $fillable = [
    'id',
    'codigo',
    'descripcion',
    'status',
  ];

  const filters = [
    'search' => ['codigo', 'descripcion'],
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
