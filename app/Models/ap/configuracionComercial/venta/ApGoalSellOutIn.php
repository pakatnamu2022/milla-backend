<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\ap\ApMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApGoalSellOutIn extends Model
{
  use SoftDeletes;

  protected $table = "ap_goal_sell_out_in";

  protected $fillable = [
    'year',
    'month',
    'goal',
    'type',
    'brand_id',
    'shop_id',
  ];

  const filters = [
    'year' => '=',
    'month' => '=',
    'type' => '=',
    'brand_id' => '=',
    'shop_id' => '=',
    'search' => ['shop.description'],
  ];

  const sorts = [
    'id',
    'year',
    'month',
    'goal',
    'type',
    'brand_id',
    'shop_id',
  ];

  public function brand()
  {
    return $this->belongsTo(ApVehicleBrand::class, 'brand_id', 'id');
  }

  public function shop()
  {
    return $this->belongsTo(ApMasters::class, 'shop_id', 'id');
  }

  public function getTotalGoalAttribute()
  {
    return static::where('shop_id', $this->shop_id)
      ->where('year', $this->year)
      ->where('month', $this->month)
      ->where('type', $this->type)
      ->sum('goal');
  }
}
