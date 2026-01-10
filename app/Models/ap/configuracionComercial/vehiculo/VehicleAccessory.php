<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use App\Models\ap\ApMasters;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use Illuminate\Database\Eloquent\Model;

class VehicleAccessory extends Model
{
  protected $table = 'ap_vehicle_accessory';

  protected $fillable = [
    'vehicle_purchase_order_id',
    'accessory_id',
    'unit_price',
    'quantity',
    'total',
  ];

  const filters = [
    'search' => ['accessory.description'],
    'vehicle_purchase_order_id' => '=',
    'accessory_id' => '=',
    'unit_price' => '=',
    'quantity' => '=',
    'total' => '=',
  ];

  const sorts = [
    'accessory_id',
    'unit_price',
    'quantity',
    'total',
  ];

  public function accessory()
  {
    return $this->belongsTo(ApMasters::class, 'accessory_id');
  }

  public function vehiclePurchaseOrder()
  {
    return $this->belongsTo(VehiclePurchaseOrder::class, 'vehicle_purchase_order_id');
  }


}
