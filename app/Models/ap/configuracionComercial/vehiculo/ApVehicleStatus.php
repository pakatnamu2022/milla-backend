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

  const USE_VENTAS = 'VENTAS';
  const USE_TALLER = 'TALLER';

  const PEDIDO_VN = 1;
  const VEHICULO_EN_TRAVESIA = 2;
  const VEHICULO_TRANSITO_DEVUELTO = 3;
  const VENDIDO_NO_ENTREGADO = 4;
  const INVENTARIO_VN = 5;
  const VENDIDO_ENTREGADO = 6;

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
