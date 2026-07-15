<?php

namespace App\Models\ap\comercial;

use App\Http\Traits\Reportable;
use App\Models\ap\comercial\ApDeliveryChecklist;
use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApVehicleDelivery extends Model
{
  use SoftDeletes, Reportable;

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
    'is_extraordinary',
    'extraordinary_approved',
    'extraordinary_approved_at',
    'extraordinary_sent_by',
    'extraordinary_token',
    'rescheduled_by',
  ];

  protected $casts = [
    'scheduled_delivery_date'   => 'datetime',
    'real_delivery_date'        => 'datetime',
    'wash_date'                 => 'datetime',
    'real_wash_date'            => 'datetime',
    'is_accounted'              => 'boolean',
    'is_extraordinary'          => 'boolean',
    'extraordinary_approved'    => 'boolean',
    'extraordinary_approved_at' => 'datetime',
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

  const string STATUS_DELIVERED = 'delivered';

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

  public function extraordinarySentBy()
  {
    return $this->belongsTo(\App\Models\User::class, 'extraordinary_sent_by');
  }

  public function rescheduledBy()
  {
    return $this->belongsTo(\App\Models\User::class, 'rescheduled_by');
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

  protected $reportColumns = [
    'vehicle.purchaseRequestQuote.holder.documentType.description' => [
      'label'    => 'TIPO DOCUMENTO',
      'fallback' => 'client.documentType.description',
    ],
    'vehicle.purchaseRequestQuote.holder.num_doc'                  => [
      'label'    => 'NÚMERO DOCUMENTO',
      'fallback' => 'client.num_doc',
    ],
    'vehicle.purchaseRequestQuote.holder.full_name'                => [
      'label'    => 'TITULAR COTIZACIÓN',
      'fallback' => 'client.full_name',
    ],
    'vehicle.model.family.brand.name'                              => [
      'label'     => 'MARCA',
      'formatter' => null,
    ],
    'vehicle.model.family.description'                             => [
      'label'     => 'MODELO',
      'formatter' => null,
    ],
    'vehicle.model.version'                                        => [
      'label'     => 'VERSIÓN',
      'formatter' => null,
    ],
    'vehicle.vin'                                                  => [
      'label'     => 'VIN',
      'formatter' => null,
    ],
    'vehicle.plate'                                                => [
      'label'   => 'PLACA',
      'default' => 'S/P',
    ],
    'vehicle.color.description'                                    => [
      'label'     => 'COLOR',
      'formatter' => null,
    ],
    'sede.abreviatura'                                             => [
      'label'     => 'SEDE',
      'formatter' => null,
    ],
    'vehicle.electronicDocumentParent.fecha_de_emision'            => [
      'label'     => 'FECHA FACTURACIÓN',
      'formatter' => 'date',
    ],
    'vehicle.electronicDocumentParent.cliente_email'               => [
      'label'     => 'EMAIL CLIENTE',
      'formatter' => null,
    ],
    'vehicle.electronicDocumentParent.client_phone'                => [
      'label'     => 'TELÉFONO CLIENTE',
      'formatter' => null,
    ],
    'advisor.nombre_completo'                                      => [
      'label'     => 'ASESOR ENTREGA',
      'formatter' => null,
    ],
    'real_delivery_date'                                           => [
      'label'     => 'FECHA ENTREGA',
      'formatter' => 'date',
    ],
    'cliente_autorizo_datos'                                       => [
      'label' => 'CLIENTE AUTORIZO DATOS',
      'value' => 'SI',
    ],
  ];

  protected $reportRelations = [
    'vehicle.purchaseRequestQuote.holder.documentType',
    'vehicle.purchaseRequestQuote.opportunity.client',
    'vehicle.purchaseRequestQuote.opportunity.worker',
    'vehicle.model.family.brand',
    'vehicle.color',
    'vehicle.electronicDocumentParent.identityDocumentType',
    'advisor',
    'sede',
    'client.documentType',
  ];

  protected $reportStyles = [
    'headerBold'            => true,
    'headerFontSize'        => 11,
    'headerFontColor'       => 'FFFFFF',
    'headerBackgroundColor' => '1565C0',
    'bodyFontSize'          => 10,
  ];

  public function generateReportSummary($data): array
  {
    return [
      'Total Entregas'  => $data->count(),
      'Completadas'     => $data->where('status_delivery', 'completed')->count(),
      'Pendientes'      => $data->where('status_delivery', 'pending')->count(),
      'Extraordinarias' => $data->where('is_extraordinary', true)->count(),
    ];
  }
}
