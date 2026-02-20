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

  const int PEDIDO_VN = 1;
  const int VEHICULO_EN_TRAVESIA = 2;
  const int VEHICULO_TRANSITO_DEVUELTO = 3;
  const int VENDIDO_NO_ENTREGADO = 4;
  const int INVENTARIO_VN = 5;
  const int VENDIDO_ENTREGADO = 6;
  const int FACTURADO = 7;
  const int CONSIGNACION = 8;

  const array STATUS = [
    self::PEDIDO_VN => 'PEDIDO VN',
    self::VEHICULO_EN_TRAVESIA => 'VEHICULO EN TRAVESIA',
    self::VEHICULO_TRANSITO_DEVUELTO => 'VEHICULO TRANSITO DEVUELTO',
    self::VENDIDO_NO_ENTREGADO => 'VENDIDO NO ENTREGADO',
    self::INVENTARIO_VN => 'INVENTARIO VN',
    self::VENDIDO_ENTREGADO => 'VENDIDO ENTREGADO',
    self::FACTURADO => 'FACTURADO',
    self::CONSIGNACION => 'CONSIGNACION',
  ];

  const array STATUS_ID = [
    'PEDIDO VN' => self::PEDIDO_VN,
    'VEHICULO EN TRAVESIA' => self::VEHICULO_EN_TRAVESIA,
    'VEHICULO TRANSITO DEVUELTO' => self::VEHICULO_TRANSITO_DEVUELTO,
    'VENDIDO NO ENTREGADO' => self::VENDIDO_NO_ENTREGADO,
    'INVENTARIO VN' => self::INVENTARIO_VN,
    'VENDIDO ENTREGADO' => self::VENDIDO_ENTREGADO,
    'FACTURADO' => self::FACTURADO,
    'CONSIGNACION' => self::CONSIGNACION,
  ];

  const array ALL_STATUS = [
    self::PEDIDO_VN,
    self::VEHICULO_EN_TRAVESIA,
    self::VEHICULO_TRANSITO_DEVUELTO,
    self::VENDIDO_NO_ENTREGADO,
    self::INVENTARIO_VN,
    self::VENDIDO_ENTREGADO,
    self::FACTURADO,
    self::CONSIGNACION,
  ];


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
