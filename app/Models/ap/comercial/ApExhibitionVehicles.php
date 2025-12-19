<?php

namespace App\Models\ap\comercial;

use App\Http\Traits\Reportable;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApExhibitionVehicles extends Model
{
  use SoftDeletes, Reportable;

  protected $table = 'ap_exhibition_vehicles';

  protected $fillable = [
    'supplier_id',
    'guia_number',
    'guia_date',
    'llegada',
    'ubicacion_id',
    'advisor_id',
    'propietario_id',
    'ap_vehicle_status_id',
    'pedido_sucursal',
    'dua_number',
    'observaciones',
    'status',
  ];

  protected $casts = [
    'guia_date' => 'date',
    'llegada' => 'date',
    'status' => 'boolean',
  ];

  public static array $filters = [
    'search' => ['guia_number', 'pedido_sucursal', 'dua_number'],
    'supplier_id' => '=',
    'advisor_id' => '=',
    'propietario_id' => '=',
    'ubicacion_id' => '=',
    'ap_vehicle_status_id' => '=',
    'status' => '=',
    'llegada' => 'between',
    'guia_date' => 'between',
  ];

  public static array $sorts = [
    'id',
    'guia_date',
    'llegada',
    'created_at',
    'updated_at',
  ];

  // Relationships
  public function supplier(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'supplier_id');
  }

  public function ubicacion(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'ubicacion_id');
  }

  public function advisor(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'advisor_id');
  }

  public function propietario(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'propietario_id');
  }

  public function vehicleStatus(): BelongsTo
  {
    return $this->belongsTo(ApVehicleStatus::class, 'ap_vehicle_status_id');
  }

  // Items relationship
  public function items(): HasMany
  {
    return $this->hasMany(ApExhibitionVehicleItems::class, 'exhibition_vehicle_id');
  }

  // Specific item types
  public function vehicleItems(): HasMany
  {
    return $this->hasMany(ApExhibitionVehicleItems::class, 'exhibition_vehicle_id')
      ->where('item_type', 'vehicle');
  }

  public function equipmentItems(): HasMany
  {
    return $this->hasMany(ApExhibitionVehicleItems::class, 'exhibition_vehicle_id')
      ->where('item_type', 'equipment');
  }

  // Report configuration
  protected $reportColumns = [
    'vehicleStatus.description' => [
      'label' => 'STATUS',
      'formatter' => null,
      'width' => 15,
    ],
    'marca' => [
      'label' => 'MARCA',
      'formatter' => null,
      'width' => 15,
      'accessor' => 'getMarcaAttribute',
    ],
    'modelo' => [
      'label' => 'MODELO',
      'formatter' => null,
      'width' => 30,
      'accessor' => 'getModeloAttribute',
    ],
    'color' => [
      'label' => 'COLOR',
      'formatter' => null,
      'width' => 20,
      'accessor' => 'getColorAttribute',
    ],
    'chasis' => [
      'label' => 'CHASIS',
      'formatter' => null,
      'width' => 20,
      'accessor' => 'getChasisAttribute',
    ],
    'motor' => [
      'label' => 'MOTOR',
      'formatter' => null,
      'width' => 20,
      'accessor' => 'getMotorAttribute',
    ],
    'anio_mod' => [
      'label' => 'AÑO MOD',
      'formatter' => null,
      'width' => 10,
      'accessor' => 'getAnioModAttribute',
    ],
    'pedido_sucursal' => [
      'label' => 'PEDIDO DE SUCURSAL',
      'formatter' => null,
      'width' => 20,
    ],
    'equipo_derco' => [
      'label' => 'EQUIPO DERCO',
      'formatter' => null,
      'width' => 30,
      'accessor' => 'getEquipoDercoAttribute',
    ],
    'llegada' => [
      'label' => 'LLEGADA',
      'formatter' => 'date',
      'width' => 12,
    ],
    'guia_number' => [
      'label' => 'N° GUIA',
      'formatter' => null,
      'width' => 15,
    ],
    'guia_date' => [
      'label' => 'FECHA GUIA',
      'formatter' => 'date',
      'width' => 12,
    ],
    'advisor.nombre_completo' => [
      'label' => 'ASESOR',
      'formatter' => null,
      'width' => 25,
    ],
    'propietario.full_name' => [
      'label' => 'PROPIETARIO',
      'formatter' => null,
      'width' => 25,
    ],
    'placa' => [
      'label' => 'PLACA',
      'formatter' => null,
      'width' => 12,
      'accessor' => 'getPlacaAttribute',
    ],
    'ubicacion.description' => [
      'label' => 'UBICACION',
      'formatter' => null,
      'width' => 15,
    ],
    'observaciones' => [
      'label' => 'OBSERVACIONES',
      'formatter' => null,
      'width' => 40,
    ],
    'dua_number' => [
      'label' => 'N° DUA',
      'formatter' => null,
      'width' => 15,
    ],
    'detalle' => [
      'label' => 'DETALLE',
      'formatter' => null,
      'width' => 30,
      'accessor' => 'getDetalleAttribute',
    ],
  ];

  protected $reportRelations = [
    'vehicleStatus',
    'items.vehicle.model.family.brand',
    'items.vehicle.color',
    'advisor',
    'propietario',
    'ubicacion',
  ];

  // Accessors for report columns
  /**
   * Get MARCA from first vehicle item
   */
  public function getMarcaAttribute()
  {
    $firstVehicle = $this->vehicleItems()->with('vehicle.model.family.brand')->first();
    return $firstVehicle?->vehicle?->model?->family?->brand?->name ?? '';
  }

  /**
   * Get MODELO from first vehicle item
   */
  public function getModeloAttribute()
  {
    $firstVehicle = $this->vehicleItems()->with('vehicle.model')->first();
    return $firstVehicle?->vehicle?->model?->version ?? '';
  }

  /**
   * Get COLOR from first vehicle item
   */
  public function getColorAttribute()
  {
    $firstVehicle = $this->vehicleItems()->with('vehicle.color')->first();
    return $firstVehicle?->vehicle?->color?->description ?? '';
  }

  /**
   * Get CHASIS (VIN) from first vehicle item
   */
  public function getChasisAttribute()
  {
    $firstVehicle = $this->vehicleItems()->with('vehicle')->first();
    return $firstVehicle?->vehicle?->vin ?? '';
  }

  /**
   * Get MOTOR (engine number) from first vehicle item
   */
  public function getMotorAttribute()
  {
    $firstVehicle = $this->vehicleItems()->with('vehicle')->first();
    return $firstVehicle?->vehicle?->engine_number ?? '';
  }

  /**
   * Get AÑO MOD from first vehicle item
   */
  public function getAnioModAttribute()
  {
    $firstVehicle = $this->vehicleItems()->with('vehicle')->first();
    return $firstVehicle?->vehicle?->year ?? '';
  }

  /**
   * Get PLACA from first vehicle item
   */
  public function getPlacaAttribute()
  {
    $firstVehicle = $this->vehicleItems()->with('vehicle')->first();
    return $firstVehicle?->vehicle?->plate ?? '';
  }

  /**
   * Get EQUIPO DERCO - concatenate all equipment items
   */
  public function getEquipoDercoAttribute()
  {
    $equipments = $this->equipmentItems()->get();

    if ($equipments->isEmpty()) {
      return '';
    }

    return $equipments->map(function ($item) {
      return $item->description . ($item->quantity > 1 ? ' (x' . $item->quantity . ')' : '');
    })->implode(', ');
  }

  /**
   * Get DETALLE - summary of all items
   */
  public function getDetalleAttribute()
  {
    $items = $this->items()->get();

    if ($items->isEmpty()) {
      return '';
    }

    $details = [];

    foreach ($items as $item) {
      if ($item->item_type === 'vehicle') {
        $details[] = 'Vehículo: ' . ($item->description ?? 'Sin descripción');
      } else {
        $details[] = 'Equipo: ' . $item->description . ($item->quantity > 1 ? ' (x' . $item->quantity . ')' : '');
      }
    }

    return implode(' | ', $details);
  }
}
