<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApOrderQuotationDetails extends Model
{
  use softDeletes;

  protected $table = 'ap_order_quotation_details';

  protected $fillable = [
    'order_quotation_id',
    'item_type',
    'product_id',
    'description',
    'purchase_price',
    'quantity',
    'unit_measure',
    'unit_price',
    'sale_price_min_original',
    'discount_percentage',
    'total_cost', //total_amount
    'net_amount',
    'tax_amount',
    'observations',
    'retail_price_external',
    'exchange_rate',
    'freight_commission',
    'created_by',
    'supply_type',
    'status',
    'is_traverse',
  ];

  const filters = [
    'search' => ['description', 'observations'],
    'order_quotation_id' => '=',
    'item_type' => '=',
    'status' => '=',
  ];

  const sorts = [
    'id',
    'unit_price',
    'quantity',
    'total_cost',
    'created_at',
  ];

  protected $casts = [
    'discount_percentage' => 'decimal:2',
    'total_cost' => 'decimal:2',
    'net_amount' => 'decimal:2',
    'tax_amount' => 'decimal:2',
    'sale_price_min_original' => 'decimal:2',
    'is_traverse' => 'boolean',
  ];

  const ITEM_TYPE_PRODUCT = 'product';
  const ITEM_TYPE_LABOR = 'labor';
  const ITEM_TYPE_MATERIAL = 'material';

  /**
   * Get all item types
   */
  public static function getItemTypes(): array
  {
    return [
      self::ITEM_TYPE_PRODUCT,
      self::ITEM_TYPE_LABOR,
      self::ITEM_TYPE_MATERIAL,
    ];
  }

  /**
   * Get validation rule for item_type
   */
  public static function getItemTypeValidationRule(): string
  {
    return 'in:' . implode(',', self::getItemTypes());
  }

  //Constants status
  const STATUS_PENDING = 'pending';
  const STATUS_TAKEN = 'taken';

  //Constants supply_type
  const SUPPLY_TYPE_STOCK = 'STOCK';
  const SUPPLY_TYPE_TRASLADO = 'TRASLADO';
  const SUPPLY_TYPE_LOCAL = 'LOCAL';
  const SUPPLY_TYPE_CENTRAL = 'CENTRAL';
  const SUPPLY_TYPE_IMPORTACION = 'IMPORTACION';
  const SUPPLY_TYPE_CENTRAL_IMPORTACION = 'CENTRAL_IMPORTACION';
  const SUPPLY_TYPE_MO = 'M.O'; // Mano de Obra

  /**
   * Get all valid supply types
   */
  public static function getSupplyTypes(): array
  {
    return [
      self::SUPPLY_TYPE_STOCK,
      self::SUPPLY_TYPE_TRASLADO,
      self::SUPPLY_TYPE_LOCAL,
      self::SUPPLY_TYPE_CENTRAL,
      self::SUPPLY_TYPE_IMPORTACION,
      self::SUPPLY_TYPE_CENTRAL_IMPORTACION,
      self::SUPPLY_TYPE_MO,
    ];
  }

  /**
   * Get validation rule for supply_type
   */
  public static function getSupplyTypeValidationRule(): string
  {
    return 'in:' . implode(',', self::getSupplyTypes());
  }

  public function setDescriptionAttribute($value): void
  {
    if ($value) {
      $this->attributes['description'] = strtoupper($value);
    }
  }

  public function setObservationsAttribute($value): void
  {
    if ($value) {
      $this->attributes['observations'] = strtoupper($value);
    }
  }

  public function orderQuotation(): BelongsTo
  {
    return $this->belongsTo(ApOrderQuotations::class, 'order_quotation_id');
  }

  public function product(): BelongsTo
  {
    return $this->belongsTo(Products::class, 'product_id');
  }

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }
}
