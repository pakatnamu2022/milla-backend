<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use App\Models\ap\ApMasters;
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
    'type_operation_id',
    'type_class_id',
    'status',
    'group_id',
  ];

  const filters = [
    'search' => ['code', 'dyn_code', 'name', 'description'],
    'type_operation_id' => '=',
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
    return $this->belongsTo(ApMasters::class, 'group_id');
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

  public function typeOperation()
  {
    return $this->belongsTo(ApMasters::class, 'type_operation_id');
  }

  public function typeClass()
  {
    return $this->belongsTo(ApMasters::class, 'type_class_id');
  }
}
