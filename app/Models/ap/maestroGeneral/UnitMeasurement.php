<?php

namespace App\Models\ap\maestroGeneral;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class UnitMeasurement extends Model
{
  use SoftDeletes;

  protected $table = 'unit_measurement';

  protected $fillable = [
    'dyn_code',
    'nubefac_code',
    'description',
    'status'
  ];

  const filters = [
    'search' => ['dyn_code', 'nubefac_code', 'description'],
    'status' => '=',
  ];

  const sorts = [
    'dyn_code',
    'nubefac_code',
    'description',
  ];

  public function setDynCodeAttribute($value)
  {
    $this->attributes['dyn_code'] = Str::upper(Str::ascii($value));
  }

  public function setNubefacCodeAttribute($value)
  {
    $this->attributes['nubefac_code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }
}
