<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApFuelType;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApVehicleInventory extends BaseModel
{
  use SoftDeletes;

  protected $table = 'ap_vehicle_inventory';

  protected $fillable = [
    'ap_vehicle_id',
    'inventory_warehouse_id',
    'vin',
    'vehicle_color_id',
    'brand_id',
    'model_id',
    'year',
    'fuel_type_id',
    'adjudication_date',
    'days',
    'limit_date',
    'reception_date',
    'is_location_confirmed',
    'is_evaluated',
    'evaluated_at',
    'evaluated_by',
    'status',
  ];

  protected $casts = [
    'is_location_confirmed' => 'boolean',
    'is_evaluated' => 'boolean',
    'status' => 'boolean',
    'evaluated_at' => 'datetime',
    'adjudication_date' => 'date',
    'limit_date' => 'date',
    'reception_date' => 'date',
    'year' => 'integer',
    'days' => 'integer',
  ];

  const array filters = [
    'search' => ['vin'],
    'ap_vehicle_id' => '=',
    'inventory_warehouse_id' => '=',
    'brand_id' => '=',
    'model_id' => '=',
    'year' => '=',
    'fuel_type_id' => '=',
    'is_location_confirmed' => '=',
    'is_evaluated' => '=',
    'status' => '=',
    'inventoryWarehouse.sede_id' => '=',
  ];

  const array sorts = [
    'vin',
    'year',
    'adjudication_date',
    'reception_date',
    'created_at',
  ];

  public function vehicle(): BelongsTo
  {
    return $this->belongsTo(Vehicles::class, 'ap_vehicle_id');
  }

  public function inventoryWarehouse(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'inventory_warehouse_id');
  }

  public function color(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'vehicle_color_id');
  }

  public function brand(): BelongsTo
  {
    return $this->belongsTo(ApVehicleBrand::class, 'brand_id');
  }

  public function model(): BelongsTo
  {
    return $this->belongsTo(ApModelsVn::class, 'model_id');
  }

  public function fuelType(): BelongsTo
  {
    return $this->belongsTo(ApFuelType::class, 'fuel_type_id');
  }

  public function evaluatedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'evaluated_by');
  }
}
