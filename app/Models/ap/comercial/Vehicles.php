<?php

namespace App\Models\ap\comercial;

use App\Http\Traits\Reportable;
use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\compras\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vehicles extends Model
{
  use SoftDeletes, Reportable;

  protected $table = 'ap_vehicles';

  protected $fillable = [
    'vin',
    'plate',
    'year',
    'engine_number',
    'ap_models_vn_id',
    'warehouse_id',
    'vehicle_color_id',
    'engine_type_id',
    'ap_vehicle_status_id',
    'type_operation_id',
    'status',
    'warehouse_physical_id',
    'customer_id'
  ];

  protected $casts = [
    'year' => 'integer',
  ];

  public static array $filters = [
    'search' => ['vin', 'plate', 'engine_number', 'year', 'ap_vehicle_status_id'],
    'ap_models_vn_id' => '=',
    'model.class_id' => '=',
    'warehouse_id' => '=',
    'ap_vehicle_status_id' => 'in',
    'vehicle_color_id' => '=',
    'engine_type_id' => '=',
    'warehouse_physical_id' => '=',
    'year' => '=',
    'has_purchase_request_quote' => 'accessor',
    'warehousePhysical.sede_id' => '=',
    'warehousePhysical.is_received' => '=',
    'warehousePhysical.article_class_id' => '=',
    'warehouse.sede_id' => '=',
    'warehouse.is_received' => '=',
    'warehouse.article_class_id' => '=',
    'is_paid' => 'accessor_bool',
    'customer_id' => '=',
    'type_operation_id' => '=',
  ];

  public static array $sorts = [
    'vin',
    'year',
    'engine_number',
    'created_at',
  ];

  public function setPlateAttribute($value)
  {
    $this->attributes['plate'] = Str::upper($value);
  }

  public function getHasPurchaseRequestQuoteAttribute(): bool
  {
    return $this->purchaseRequestQuote()->exists();
  }

  // Relaciones
  public function purchaseRequestQuote(): HasOne
  {
    return $this->hasOne(PurchaseRequestQuote::class, 'ap_vehicle_id');
  }

  public function model(): BelongsTo
  {
    return $this->belongsTo(ApModelsVn::class, 'ap_models_vn_id');
  }

  public function color(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'vehicle_color_id');
  }

  public function engineType(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'engine_type_id');
  }

  public function vehicleStatus(): BelongsTo
  {
    return $this->belongsTo(ApVehicleStatus::class, 'ap_vehicle_status_id');
  }

  public function warehousePhysical(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_physical_id');
  }

  public function warehouse(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_id');
  }

  public function vehicleMovements(): HasMany
  {
    return $this->hasMany(VehicleMovement::class, 'ap_vehicle_id');
  }

  public function customer(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'customer_id');
  }

  public function typeOperation(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'type_operation_id');
  }

  /**
   * Obtiene todas las órdenes de compra a través de los movimientos del vehículo
   * Un vehículo puede tener múltiples movimientos y cada movimiento puede tener una orden
   */
  public function purchaseOrders(): HasManyThrough
  {
    return $this->hasManyThrough(
      PurchaseOrder::class,       // Modelo final que queremos obtener
      VehicleMovement::class,     // Modelo intermedio
      'ap_vehicle_id',            // Foreign key en vehicle_movement que apunta a vehicles
      'vehicle_movement_id',      // Foreign key en purchase_order que apunta a vehicle_movement
      'id',                       // Local key en vehicles
      'id'                        // Local key en vehicle_movement
    );
  }

  public function purchaseOrder(): HasOneThrough
  {
    return $this->HasOneThrough(
      PurchaseOrder::class,       // Modelo final que queremos obtener
      VehicleMovement::class,     // Modelo intermedio
      'ap_vehicle_id',            // Foreign key en vehicle_movement que apunta a vehicles
      'vehicle_movement_id',      // Foreign key en purchase_order que apunta a vehicle_movement
      'id',                       // Local key en vehicles
      'id'                        // Local key en vehicle_movement
    )->whereNull('ap_purchase_order.deleted_at');
  }

  /**
   * Obtiene todas las guías de remisión a través de los movimientos del vehículo
   * Un vehículo puede tener múltiples movimientos y cada movimiento puede tener una guía
   */
  public function shippingGuides(): HasManyThrough
  {
    return $this->hasManyThrough(
      ShippingGuides::class,      // Modelo final que queremos obtener
      VehicleMovement::class,     // Modelo intermedio
      'ap_vehicle_id',            // Foreign key en vehicle_movement que apunta a vehicles
      'vehicle_movement_id',      // Foreign key en shipping_guides que apunta a vehicle_movement
      'id',                       // Local key en vehicles
      'id'                        // Local key en vehicle_movement
    );
  }

  /**
   * Reception
   * @return HasOneThrough
   */
  public function shippingGuideReceiving(): HasOneThrough
  {
    return $this->HasOneThrough(
      ShippingGuides::class,      // Modelo final que queremos obtener
      VehicleMovement::class,     // Modelo intermedio
      'ap_vehicle_id',            // Foreign key en vehicle_movement que apunta a vehicles
      'vehicle_movement_id',      // Foreign key en shipping_guides que apunta a vehicle_movement
      'id',                       // Local key en vehicles
      'id'                        // Local key en vehicle_movement
    )->whereHas('receivingChecklists');
  }

  public function vehicleDelivery(): BelongsTo
  {
    return $this->belongsTo(ApVehicleDelivery::class, 'id', 'vehicle_id');
  }

  /**
   * Obtiene todos los documentos electrónicos (facturas, boletas, etc.) a través de los movimientos del vehículo
   * Un vehículo puede tener múltiples movimientos y cada movimiento puede tener documentos electrónicos
   */
  public function electronicDocuments(): HasManyThrough
  {
    return $this->hasManyThrough(
      ElectronicDocument::class, // Modelo final
      VehicleMovement::class,                                // Modelo intermedio
      'ap_vehicle_id',                                       // Foreign key en vehicle_movement que apunta a vehicles
      'ap_vehicle_movement_id',                              // Foreign key en electronic_documents que apunta a vehicle_movement
      'id',                                                  // Local key en vehicles
      'id'                                                   // Local key en vehicle_movement
    );
  }

  /**
   * Obtiene el documento electrónico y el cliente asociado a un vehículo
   *
   * @param int $vehicleId ID del vehículo
   * @return object Objeto con electronicDocument, client y vehicle
   * @throws \Exception Si no se encuentra el documento o el cliente
   */
  public static function getElectronicDocumentWithClient($vehicleId)
  {
    // Obtener el vehículo
    $vehicle = self::find($vehicleId);
    if (!$vehicle) {
      throw new \Exception('Vehículo no encontrado');
    }

    // Obtener el documento electrónico
    $electronicDocument = ElectronicDocument::whereHas('vehicleMovement', function ($query) use ($vehicleId) {
      $query->where('ap_vehicle_id', $vehicleId);
    })
      ->where('aceptada_por_sunat', true)
      ->where('anulado', false)
      ->whereNotNull('client_id')
      ->whereNotNull('purchase_request_quote_id')
      ->with(['client', 'purchaseRequestQuote'])
      ->orderBy('fecha_de_emision', 'desc')
      ->first();

    if (!$electronicDocument || !$electronicDocument->client_id) {
      throw new \Exception('No se encontró factura ni cliente asociado al vehículo');
    }

    if (!$electronicDocument->purchase_request_quote_id) {
      throw new \Exception('No se encontró cotización asociada al vehículo');
    }

    return (object)[
      'vehicle' => $vehicle,
      'electronicDocument' => $electronicDocument,
      'client' => $electronicDocument->client
    ];
  }

  /**
   * Valida si un vehículo está completamente pagado
   *
   * @param int $vehicleId ID del vehículo
   * @return bool
   */
  public static function isVehiclePaid($vehicleId): bool
  {
    try {
      // Obtener el documento electrónico usando el método centralizado
      $data = self::getElectronicDocumentWithClient($vehicleId);
      $electronicDocument = $data->electronicDocument;

      $purchaseRequestQuote = $electronicDocument->purchaseRequestQuote;
      $totalSalePrice = $purchaseRequestQuote->sale_price;

      // Obtener todos los documentos electrónicos asociados a esta cotización
      $documents = ElectronicDocument::where('purchase_request_quote_id', $purchaseRequestQuote->id)
        ->where('aceptada_por_sunat', true)
        ->where('anulado', false)
        ->get();

      // Calcular total pagado
      $totalPaid = 0;
      foreach ($documents as $doc) {
        // Facturas y boletas suman al total pagado
        if (in_array($doc->sunat_concept_document_type_id, [
          ElectronicDocument::TYPE_FACTURA,
          ElectronicDocument::TYPE_BOLETA
        ])) {
          $totalPaid += $doc->total;
        } // Notas de crédito restan del total pagado
        elseif ($doc->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO) {
          $totalPaid -= $doc->total;
        } // Notas de débito suman al total pagado
        elseif ($doc->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_DEBITO) {
          $totalPaid += $doc->total;
        }
      }

      // Calcular deuda pendiente
      $pendingDebt = $totalSalePrice - $totalPaid;

      // Consideramos pagado si la diferencia es menor a 0.01
      return abs($pendingDebt) < 0.01;
    } catch (\Exception $e) {
      return false;
    }
  }

  public function getIsPaidAttribute(): bool
  {
    return self::isVehiclePaid($this->id);
  }

  public function getPurchasePriceAttribute(): float
  {
    $purchaseOrder = $this->purchaseOrder;
    return $purchaseOrder ? $purchaseOrder->total : 0.0;
  }

  public function electronicDocumentParent(): HasOneThrough
  {
    return $this->HasOneThrough(
      ElectronicDocument::class,
      VehicleMovement::class,
      'ap_vehicle_id',
      'ap_vehicle_movement_id',
      'id',
      'id'
    )->where('is_advance_payment', false);
  }

  protected $reportColumns = [
    'electronicDocumentParent.seriesModel.sede.suc_abrev' => [
      'label' => 'PISO',
      'formatter' => null,
    ],
    'electronicDocumentParent.seriesModel.sede.shop.description' => [
      'label' => 'SEDE',
      'formatter' => null,
    ],
    'model.family.brand.group.description' => [
      'label' => 'GRUPOS',
      'formatter' => null,
    ],
    'model.family.brand.name' => [
      'label' => 'MARCA',
      'formatter' => null,
    ],
    'model.family.description' => [
      'label' => 'MODELO',
      'formatter' => null,
    ],
    'model.version' => [
      'label' => 'VERSION',
      'formatter' => null,
    ],
    'color.description' => [
      'label' => 'COLOR',
      'formatter' => null,
    ],
    'vin' => [
      'label' => 'VIN',
      'formatter' => null,
    ],
    'engine_number' => [
      'label' => 'NRO. MOTOR',
      'formatter' => null,
    ],
    'electronicDocumentParent.full_number' => [
      'label' => 'NRO. FACTURA',
      'formatter' => null,
    ],
    'electronicDocumentParent.cliente_numero_de_documento' => [
      'label' => 'NRO DOCUMENTO',
      'formatter' => null,
    ],
    'electronicDocumentParent.cliente_denominacion' => [
      'label' => 'CLIENTE',
      'formatter' => null,
    ],
    'electronicDocumentParent.client_phone' => [
      'label' => 'CELULAR',
      'formatter' => null,
    ],
    'electronicDocumentParent.cliente_email' => [
      'label' => 'EMAIL',
      'formatter' => null,
    ],
    'electronicDocumentParent.purchaseRequestQuote.opportunity.worker.nombre_completo' => [
      'label' => 'ASESOR',
      'formatter' => null,
    ],
    'purchaseOrder.supplierOrderType.description' => [
      'label' => 'FECHA COMPRA',
      'formatter' => 'date',
    ],
//    DATES
    'purchaseOrder.emission_date' => [
      'label' => 'FECHA COMPRA',
      'formatter' => 'date',
    ],
    'shippingGuideReceiving.received_date' => [
      'label' => 'FECHA RECEPCIÓN',
      'formatter' => 'date',
    ],
    'electronicDocumentParent.sale_date' => [
      'label' => 'FECHA VENTA',
      'formatter' => 'date',
    ],
    'vehicleDelivery.real_delivery_date' => [
      'label' => 'FECHA ENTREGA',
      'formatter' => 'date',
    ],
  ];

  protected $reportRelations = [
    'electronicDocumentParent.seriesModel.sede.shop',
    'electronicDocumentParent.purchaseRequestQuote.opportunity.worker',
    'model.family.brand.group',
    'color',
    'shippingGuideReceiving',
    'purchaseOrder.supplierOrderType',
    'vehicleDelivery',
  ];
}
