<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApFuelType extends Model
{
  use SoftDeletes;

  protected $table = 'ap_fuel_type';

  protected $fillable = [
    'id',
    'code',
    'description',
    'electric_motor',
    'status',
  ];

  const filters = [
    'search' => ['code', 'description'],
    'status' => '=',
  ];

  const sorts = [
    'code',
    'description',
  ];

  public function setCodeAttribute($value)
  {
    $this->attributes['code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }
}
