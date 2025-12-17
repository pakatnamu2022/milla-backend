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
    'code',
    'description',
    'status',
    'brand_id',
  ];

  const filters = [
    'search' => ['code', 'description'],
    'brand_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'code',
    'description',
  ];

  public function models()
  {
    return $this->hasMany(ApModelsVn::class, 'family_id');
  }

  public function brand()
  {
    return $this->belongsTo(ApVehicleBrand::class, 'brand_id');
  }

  public function setCodeAttribute($value)
  {
    $this->attributes['code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }
}
