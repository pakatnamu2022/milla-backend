<?php

namespace App\Models\ap\postventa\taller;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApVehicleInspectionDamages extends Model
{
  use SoftDeletes;

  protected $table = 'ap_vehicle_inspection_damages';

  protected $fillable = [
    'vehicle_inspection_id',
    'damage_type',
    'x_coordinate',
    'y_coordinate',
    'description',
    'photo_url',
  ];

  public function vehicleInspection()
  {
    return $this->belongsTo(ApVehicleInspection::class, 'vehicle_inspection_id');
  }
}
