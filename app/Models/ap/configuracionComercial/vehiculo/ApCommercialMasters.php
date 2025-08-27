<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApCommercialMasters extends Model
{
  use SoftDeletes;

  protected $table = 'ap_commercial_masters';

  protected $fillable = [
    'id',
    'codigo',
    'descripcion',
    'tipo',
    'status',
  ];

  const filters = [
    'search' => ['codigo', 'descripcion', 'tipo'],
    'tipo' => '=',
    'status' => '='
  ];

  const sorts = [
    'codigo',
    'descripcion',
    'tipo',
  ];

  public function setCodigoAttribute($value)
  {
    $this->attributes['codigo'] = Str::upper(Str::ascii($value));
  }

  public function setDescripcionAttribute($value)
  {
    $this->attributes['descripcion'] = Str::upper(Str::ascii($value));
  }

  public function setTipoAttribute($value)
  {
    $this->attributes['tipo'] = Str::upper(Str::ascii($value));
  }
}
