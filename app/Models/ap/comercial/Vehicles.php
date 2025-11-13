<?php

namespace App\Models\ap\comercial;

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
    'warehouse_id',
    'vehicle_color_id',
    'engine_type_id',
    'ap_vehicle_status_id',
    'type_operation_id',
    'status',
    'warehouse_physical_id',
  ];

  protected $casts = [
    'year' => 'integer',
  ];

  public static array $filters = [
    'search' => ['vin', 'engine_number', 'year', 'ap_vehicle_status_id'],
    'ap_models_vn_id' => '=',
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
  ];

  public static array $sorts = [
    'vin',
    'year',
    'engine_number',
    'created_at',
  ];

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
}
