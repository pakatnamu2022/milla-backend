<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApVehicleBrand extends Model
{
  use SoftDeletes;

  protected $table = 'ap_vehicle_brand';

  protected $fillable = [
    'codigo',
    'codigo_dyn',
    'nombre',
    'descripcion',
    'logo',
    'logo_min',
    'status',
    'grupo_id',
  ];

  const filters = [
    'search' => ['codigo', 'codigo_dyn', 'nombre', 'descripcion'],
    'status' => '=',
  ];

  const sorts = [
    'codigo',
    'codigo_dyn',
    'nombre',
  ];

  public function grupo()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'grupo_id');
  }

  public function setCodigoAttribute($value)
  {
    $this->attributes['codigo'] = Str::upper(Str::ascii($value));
  }

  public function setCodigoDynAttribute($value)
  {
    $this->attributes['codigo_dyn'] = Str::upper(Str::ascii($value));
  }

  public function setNameAttribute($value)
  {
    $this->attributes['name'] = Str::upper(Str::ascii($value));
  }

  public function setDescripcionAttribute($value)
  {
    $this->attributes['descripcion'] = Str::upper(Str::ascii($value));
  }
}
