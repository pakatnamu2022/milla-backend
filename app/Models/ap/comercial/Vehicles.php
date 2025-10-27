<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicles extends Model
{
  use SoftDeletes;

  protected $table = 'ap_vehicles';

  protected $fillable = [
    'vin',
    'year',
    'engine_number',
    'ap_models_vn_id',
    'vehicle_color_id',
    'supplier_order_type_id',
    'engine_type_id',
    'ap_vehicle_status_id',
    'sede_id',
    'warehouse_physical_id',
  ];

  protected $casts = [
    'year' => 'integer',
  ];

  // Relaciones
  public function model(): BelongsTo
  {
    return $this->belongsTo(ApModelsVn::class, 'ap_models_vn_id');
  }

  public function color(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'vehicle_color_id');
  }

  public function supplierOrderType(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'supplier_order_type_id');
  }

  public function engineType(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'engine_type_id');
  }

  public function status(): BelongsTo
  {
    return $this->belongsTo(ApVehicleStatus::class, 'ap_vehicle_status_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function warehousePhysical(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_physical_id');
  }

  public function vehicleMovements(): HasMany
  {
    return $this->hasMany(VehicleMovement::class, 'ap_vehicle_id');
  }
}
