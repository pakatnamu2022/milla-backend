<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApFamilies extends Model
{
  use SoftDeletes;

  protected $table = 'ap_families';

  protected $fillable = [
    'codigo',
    'descripcion',
    'status',
    'marca_id',
  ];

  const filters = [
    'search' => ['codigo', 'descripcion'],
  ];

  const sorts = [
    'codigo',
    'descripcion',
  ];

  public function marca()
  {
    return $this->belongsTo(ApVehicleBrand::class, 'marca_id');
  }

  public function setCodigoAttribute($value)
  {
    $this->attributes['codigo'] = Str::upper(Str::ascii($value));
  }

  public function setDescripcionAttribute($value)
  {
    $this->attributes['descripcion'] = Str::upper(Str::ascii($value));
  }
}
