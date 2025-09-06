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
    'code',
    'description',
    'type',
    'status',
  ];

  const filters = [
    'search' => ['code', 'description', 'type'],
    'type' => '=',
    'status' => '='
  ];

  const sorts = [
    'code',
    'description',
    'type',
  ];

  public function setCodeAttribute($value)
  {
    $this->attributes['code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function setTypeAttribute($value)
  {
    $this->attributes['type'] = Str::upper(Str::ascii($value));
  }
}
