<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use App\Models\ap\ApCommercialMasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApVehicleBrand extends Model
{
  use SoftDeletes;

  protected $table = 'ap_vehicle_brand';

  protected $fillable = [
    'code',
    'dyn_code',
    'name',
    'description',
    'logo',
    'logo_min',
    'is_commercial',
    'status',
    'group_id',
  ];

  const filters = [
    'search' => ['code', 'dyn_code', 'name', 'description'],
    'is_commercial' => '=',
    'status' => '=',
    'sede_id' => 'accessor'
  ];

  const sorts = [
    'code',
    'dyn_code',
    'name',
  ];

  public function getSedeIdAttribute()
  {
    return $this->group?->sede_id;
  }

  public function group()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'group_id');
  }

  public function setCodeAttribute($value)
  {
    $this->attributes['code'] = Str::upper(Str::ascii($value));
  }

  public function setDynCodeAttribute($value)
  {
    $this->attributes['dyn_code'] = Str::upper(Str::ascii($value));
  }

  public function setNameAttribute($value)
  {
    $this->attributes['name'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }
}
