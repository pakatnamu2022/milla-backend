<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApVehicleStatus extends Model
{
  use SoftDeletes;

  protected $table = "ap_vehicle_status";

  protected $fillable = [
    'code',
    'description',
    'use',
    'color',
    'status'
  ];

  const filters = [
    'search' => ['code', 'description'],
    'uso' => '=',
  ];

  const sorts = [
    'code',
    'description',
  ];

  const PEDIDO_VN = 28;
  const VEHICULO_EN_TRAVESIA = 38;

  public function setCodeAttribute($value)
  {
    $this->attributes['code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function setUseAttribute($value)
  {
    $this->attributes['use'] = Str::upper(Str::ascii($value));
  }

  public function setColorAttribute($value)
  {
    $this->attributes['color'] = Str::upper(Str::ascii($value));
  }
}
