<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehiclePurchaseOrder extends Model
{
  use softDeletes;

  protected $table = 'ap_vehicle_purchase_order';

  protected $fillable = [
//    vehicle
    'vin',
    'year',
    'engine_number',
    'status',
    'ap_models_vn_id',
    'vehicle_color_id',
    'supplier_order_type_id',
    'engine_type_id',
    'ap_vehicle_status_id',
    'sede_id',

//    Invoice
    'invoice_series',
    'invoice_number',
    'emission_date',
    'unit_price',
    'discount',
    'subtotal',
    'igv',
    'total',
    'supplier_id',
    'currency_id',
    'exchange_rate_id',

//    Guide
    'number',
    'number_guide',
    'warehouse_id',
    'warehouse_physical_id',

  ];

  const filters = [
    'search' => ['vin', 'order_number', 'engine_number'],
  ];

  const sorts = [
    'vin',
  ];

  public function model(): BelongsTo
  {
    return $this->belongsTo(ApModelsVn::class, 'ap_models_vn_id');
  }

  public function color(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'vehicle_color_id');
  }

  public function supplierType(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'supplier_order_type_id');
  }

  public function engineType(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'engine_type_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function vehicleStatus(): BelongsTo
  {
    return $this->belongsTo(ApVehicleStatus::class, 'ap_vehicle_status_id');
  }
}
