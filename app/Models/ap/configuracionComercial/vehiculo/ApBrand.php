<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApBrand extends Model
{
  use SoftDeletes;

  protected $table = 'ap_marca_vehiculo';

  protected $fillable = [
    'id',
    'codigo',
    'codigo_dyn',
    'grupo_id',
    'name',
    'descripcion',
    'logo',
    'logo_min',
  ];

  const filters = [
    'search' => ['codigo', 'codigo_dyn', 'name', 'descripcion'],
  ];

  const sorts = [
    'id',
    'codigo',
    'codigo_dyn',
    'name',
    'descripcion',
  ];

  public function grupo()
  {
    return $this->belongsTo(ApBrandGroups::class, 'grupo_id');
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
