<?php

namespace App\Models\ap\comercial;

use App\Models\ap\comercial\ApDeliveryChecklist;
use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApVehicleDelivery extends Model
{
  use softDeletes;

  protected $table = 'ap_vehicle_delivery';

  protected $fillable = [
    'advisor_id',
    'vehicle_id',
    'scheduled_delivery_date',
    'wash_date',
    'real_delivery_date',
    'real_wash_date',
    'observations',
    'sede_id',
    'status_wash',
    'status_delivery',
    'shipping_guide_id',
    'vehicle_movement_id',
    'ap_class_article_id',
    'client_id',
    'is_accounted',
  ];

  protected $casts = [
    'scheduled_delivery_date' => 'datetime',
    'real_delivery_date'      => 'datetime',
    'wash_date'               => 'datetime',
    'real_wash_date'          => 'datetime',
    'is_accounted'            => 'boolean',
  ];

  const WEEKDAY_SLOTS = ['09:00', '10:00', '11:00', '12:00', '15:00', '16:00', '17:00'];
  const SATURDAY_SLOTS = ['10:00', '11:00', '12:00'];

  const filters = [
    'search'                  => ['vehicle.vin', 'advisor.nombre_completo'],
    'vehicle_id'              => '=',
    'scheduled_delivery_date' => 'date_between',
    'real_delivery_date'      => 'date_between',
    'advisor_id'              => '=',
    'sede_id'                 => '=',
    'sede.shop_id'            => '=',
    'status_delivery'         => '=',
    'status_wash'             => '=',
    'has_vehicle_delivery'    => 'accessor_bool',
  ];

  const sorts = [
    'id',
    'advisor_id',
    'vehicle_id',
    'scheduled_delivery_date',
    'real_delivery_date',
  ];

  public function setObservationsAttribute($value)
  {
    $this->attributes['observations'] = strtoupper($value);
  }

  public function advisor()
  {
    return $this->belongsTo(Worker::class, 'advisor_id');
  }

  public function vehicle()
  {
    return $this->belongsTo(Vehicles::class, 'vehicle_id');
  }

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function ShippingGuide()
  {
    return $this->belongsTo(ShippingGuides::class, 'shipping_guide_id')->whereNull('cancelled_at');
  }

  public function vehicleMovement()
  {
    return $this->belongsTo(VehicleMovement::class, 'vehicle_movement_id');
  }

  public function apClassArticle()
  {
    return $this->belongsTo(ApClassArticle::class, 'ap_class_article_id');
  }

  public function client()
  {
    return $this->belongsTo(BusinessPartners::class, 'client_id');
  }

  public function deliveryChecklist()
  {
    return $this->hasOne(ApDeliveryChecklist::class, 'vehicle_delivery_id');
  }

  public function getChecklistStatusAttribute(): ?string
  {
    return $this->deliveryChecklist?->status ?? null;
  }

  public function getHasVehicleDeliveryAttribute(): bool
  {
    return $this->vehicle_id !== null
      && self::where('vehicle_id', $this->vehicle_id)
        ->whereNull('deleted_at')
        ->exists();
  }
}
