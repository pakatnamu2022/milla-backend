<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleVN extends Model
{
  use softDeletes;

  protected $table = 'vehicle_vn';

  protected $fillable = [
    'vin',
    'order_number',
    'year',
    'engine_number',
    'status',
    'ap_models_vn_id',
    'vehicle_color_id',
    'supplier_order_type_id',
    'engine_type_id',
    'sede_id',
  ];

  const filters = [
    'search' => ['vin', 'num_pedido', 'num_motor'],
    'year' => '=',
    'status' => '=',
    'ap_models_vn_id' => '=',
    'vehicle_color_id' => '=',
    'supplier_order_type_id' => '=',
    'engine_type_id' => '=',
    'sede_id' => '=',
  ];

  const sorts = [
    'vin',
    'num_pedido',
    'year',
    'month',
    'num_motor',
  ];

  public function modelVN(): BelongsTo
  {
    return $this->belongsTo(ApModelsVn::class, 'ap_models_vn_id');
  }

  public function vehicleColor(): BelongsTo
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

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }
}
