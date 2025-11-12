<?php

namespace App\Models\ap\comercial;

use App\Models\ap\compras\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiclePurchaseOrderMigrationLog extends Model
{
  protected $table = 'ap_vehicle_purchase_order_migration_log';

  protected $fillable = [
    'vehicle_purchase_order_id',
    'shipping_guide_id',
    'ap_vehicles_id',
    'step',
    'status',
    'table_name',
    'external_id',
    'proceso_estado',
    'error_message',
    'attempts',
    'last_attempt_at',
    'completed_at',
  ];

  protected $casts = [
    'last_attempt_at' => 'datetime',
    'completed_at' => 'datetime',
    'attempts' => 'integer',
    'proceso_estado' => 'integer',
  ];

  // Constantes para los pasos de migración (Purchase Orders)
  const STEP_SUPPLIER = 'supplier';
  const STEP_SUPPLIER_ADDRESS = 'supplier_address';
  const STEP_ARTICLE = 'article';
  const STEP_PURCHASE_ORDER = 'purchase_order';
  const STEP_PURCHASE_ORDER_DETAIL = 'purchase_order_detail';
  const STEP_RECEPTION = 'reception';
  const STEP_RECEPTION_DETAIL = 'reception_detail';
  const STEP_RECEPTION_DETAIL_SERIAL = 'reception_detail_serial';

  // Constantes para los pasos de migración (Shipping Guides)
  const STEP_INVENTORY_TRANSFER = 'inventory_transfer';
  const STEP_INVENTORY_TRANSFER_DETAIL = 'inventory_transfer_detail';
  const STEP_INVENTORY_TRANSFER_SERIAL = 'inventory_transfer_serial';

  // Constantes para los pasos de reversión (Cancelación de Shipping Guides)
  const STEP_INVENTORY_TRANSFER_REVERSAL = 'inventory_transfer_REVERSAL';
  const STEP_INVENTORY_TRANSFER_DETAIL_REVERSAL = 'inventory_transfer_detail_REVERSAL';
  const STEP_INVENTORY_TRANSFER_SERIAL_REVERSAL = 'inventory_transfer_serial_REVERSAL';

  // Constantes para los pasos de migración (Shipping Guides - Ventas)
  const STEP_SALE_SHIPPING_GUIDE = 'sale_shipping_guide';
  const STEP_SALE_SHIPPING_GUIDE_DETAIL = 'sale_shipping_guide_detail';
  const STEP_SALE_SHIPPING_GUIDE_SERIAL = 'sale_shipping_guide_serial';

  // Constantes para los pasos de reversión (Cancelación de Shipping Guides - Ventas)
  const STEP_SALE_SHIPPING_GUIDE_REVERSAL = 'sale_shipping_guide_REVERSAL';
  const STEP_SALE_SHIPPING_GUIDE_DETAIL_REVERSAL = 'sale_shipping_guide_detail_REVERSAL';
  const STEP_SALE_SHIPPING_GUIDE_SERIAL_REVERSAL = 'sale_shipping_guide_serial_REVERSAL';

  // Constantes para los estados
  const STATUS_PENDING = 'pending';
  const STATUS_IN_PROGRESS = 'in_progress';
  const STATUS_COMPLETED = 'completed';
  const STATUS_FAILED = 'failed';

  // Mapeo de pasos a tablas intermedias
  const STEP_TABLE_MAPPING = [
    self::STEP_SUPPLIER => 'neInTbProveedor',
    self::STEP_SUPPLIER_ADDRESS => 'neInTbProveedorDireccion',
    self::STEP_ARTICLE => 'neInTbArticulo',
    self::STEP_PURCHASE_ORDER => 'neInTbOrdenCompra',
    self::STEP_PURCHASE_ORDER_DETAIL => 'neInTbOrdenCompraDet',
    self::STEP_RECEPTION => 'neInTbRecepcion',
    self::STEP_RECEPTION_DETAIL => 'neInTbRecepcionDt',
    self::STEP_RECEPTION_DETAIL_SERIAL => 'neInTbRecepcionDtS',
    self::STEP_INVENTORY_TRANSFER => 'neInTbTransferenciaInventario',
    self::STEP_INVENTORY_TRANSFER_DETAIL => 'neInTbTransferenciaInventarioDet',
    self::STEP_INVENTORY_TRANSFER_SERIAL => 'neInTbTransferenciaInventarioDtS',
    self::STEP_SALE_SHIPPING_GUIDE => 'neInTbTransaccionInventario',
    self::STEP_SALE_SHIPPING_GUIDE_DETAIL => 'neInTbTransaccionInventarioDet',
    self::STEP_SALE_SHIPPING_GUIDE_SERIAL => 'neInTbTransaccionInventarioDtS',
  ];

  /**
   * Relación con la orden de compra de vehículo
   */
  public function purchaseOrder(): BelongsTo
  {
    return $this->belongsTo(PurchaseOrder::class, 'vehicle_purchase_order_id');
  }

  /**
   * Relación con la guía de remisión
   */
  public function shippingGuide(): BelongsTo
  {
    return $this->belongsTo(ShippingGuides::class, 'shipping_guide_id');
  }

  /**
   * Relación con el vehículo
   */
  public function vehicle(): BelongsTo
  {
    return $this->belongsTo(Vehicles::class, 'ap_vehicles_id');
  }

  /**
   * Marca el paso como en progreso
   */
  public function markAsInProgress(): void
  {
    $this->update([
      'status' => self::STATUS_IN_PROGRESS,
      'last_attempt_at' => now(),
      'attempts' => $this->attempts + 1,
    ]);
  }

  /**
   * Marca el paso como completado
   */
  public function markAsCompleted(?int $procesoEstado = null): void
  {
    $this->update([
      'status' => self::STATUS_COMPLETED,
      'completed_at' => now(),
      'proceso_estado' => $procesoEstado,
      'error_message' => null,
    ]);
  }

  /**
   * Marca el paso como fallido
   */
  public function markAsFailed(string $errorMessage, ?int $procesoEstado = null): void
  {
    $this->update([
      'status' => self::STATUS_FAILED,
      'error_message' => $errorMessage,
      'proceso_estado' => $procesoEstado,
      'last_attempt_at' => now(),
    ]);
  }

  /**
   * Actualiza el estado de proceso desde la BD intermedia
   */
  public function updateProcesoEstado(int $procesoEstado, ?string $errorMessage = null): void
  {
    $data = [
      'proceso_estado' => $procesoEstado,
      'last_attempt_at' => now(),
    ];

    if ($procesoEstado === 1) {
      $data['status'] = self::STATUS_COMPLETED;
      $data['completed_at'] = now();
      $data['error_message'] = null;
    } elseif ($errorMessage) {
      $data['error_message'] = $errorMessage;
    }

    $this->update($data);
  }

  /**
   * Scope para obtener solo logs completados
   */
  public function scopeCompleted($query)
  {
    return $query->where('status', self::STATUS_COMPLETED);
  }

  /**
   * Scope para obtener solo logs fallidos
   */
  public function scopeFailed($query)
  {
    return $query->where('status', self::STATUS_FAILED);
  }

  /**
   * Scope para obtener solo logs pendientes
   */
  public function scopePending($query)
  {
    return $query->where('status', self::STATUS_PENDING);
  }

  /**
   * Scope para obtener solo logs en progreso
   */
  public function scopeInProgress($query)
  {
    return $query->where('status', self::STATUS_IN_PROGRESS);
  }

  /**
   * Scope para filtrar por paso específico
   */
  public function scopeForStep($query, string $step)
  {
    return $query->where('step', $step);
  }
}
