<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApSupplierOrder extends Model
{
  use softDeletes;

  protected $table = 'ap_supplier_order';

  protected $fillable = [
    'ap_purchase_order_id',
    'supplier_id',
    'sede_id',
    'warehouse_id',
    'type_currency_id',
    'created_by',
    'order_date',
    'order_number',
    'supply_type',
    'net_amount',
    'tax_amount',
    'total_amount',
    'exchange_rate',
    'is_take',
    'status',
  ];

  const filters = [
    'search' => ['order_number', 'supplier.num_doc', 'supplier.full_name'],
    'supplier_id' => '=',
    'sede_id' => '=',
    'warehouse_id' => '=',
    'type_currency_id' => '=',
    'created_by' => '=',
    'order_date' => 'between',
    'supply_type' => 'in',
    'is_take' => '=',
    'status' => '=',
  ];

  const sorts = [
    'id',
    'order_number',
    'order_date',
    'created_at',
    'updated_at',
  ];

  // SUPPLY TYPE CONSTANTS
  const STOCK = 'STOCK';
  const LIMA = 'LIMA';
  const IMPORTACION = 'IMPORTACION';

  protected static function boot()
  {
    parent::boot();

    // when deleting a quotation, also delete its details
    static::deleting(function ($quotation) {
      $quotation->details()->delete();
    });
  }

  public function apPurchaseOrder(): BelongsTo
  {
    return $this->belongsTo(PurchaseOrder::class, 'ap_purchase_order_id');
  }

  public function supplier(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'supplier_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function warehouse(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_id');
  }

  public function typeCurrency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'type_currency_id');
  }

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function details()
  {
    return $this->hasMany(ApSupplierOrderDetails::class, 'ap_supplier_order_id');
  }

  /**
   * Relación con los detalles de solicitudes de compra
   */
  public function requestDetails(): BelongsToMany
  {
    return $this->belongsToMany(
      ApOrderPurchaseRequestDetails::class,
      'ap_order_purchase_request_detail_supplier_order',
      'ap_supplier_order_id',
      'ap_order_purchase_request_detail_id'
    )->withTimestamps();
  }

  /**
   * Obtener usuarios únicos que solicitaron productos en esta orden de compra
   * Para notificarles cuando lleguen los productos
   */
  public function getUsersToNotify()
  {
    return $this->requestDetails()
      ->with('orderPurchaseRequest.requestedBy.person')
      ->get()
      ->pluck('orderPurchaseRequest')
      ->unique('id')
      ->filter()
      ->map(function ($request) {
        return [
          'request_id' => $request->id,
          'request_number' => $request->request_number,
          'user_id' => $request->requested_by,
          'user_name' => $request->requestedBy?->person?->nombre_completo ?? 'Usuario',
          'email' => $request->requestedBy?->person?->email2,
        ];
      })
      ->whereNotNull('email')
      ->unique('email')
      ->values();
  }

}
