<?php

namespace App\Models\ap\postventa\taller;

use App\Http\Utils\Constants;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\DiscountRequestsOrderQuotation;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/*
  Modelo para las cotizaciones
*/

class ApOrderQuotations extends Model
{
  use softDeletes;

  protected $table = 'ap_order_quotations';

  protected $fillable = [
    'vehicle_id',
    'client_id',
    'sede_id',
    'quotation_number',
    'subtotal',
    'discount_percentage',
    'discount_amount',
    'tax_amount',
    'total_amount',
    'validity_days',
    'quotation_date',
    'expiration_date',
    'collection_date',
    'observations',
    'created_by',
    'is_take',
    'is_requested_by_management',
    'emails_sent_count',
    'area_id',
    'currency_id',
    'exchange_rate',
    'has_invoice_generated',
    'is_fully_paid',
    'output_generation_warehouse',
    'discard_reason_id',
    'discarded_note',
    'discarded_by',
    'discarded_at',
    'supply_type',
    'customer_signature_url',
    'customer_signature_delivery_url',
    'delivery_document_number',
    'chief_approval_by',
    'manager_approval_by',
    'status',
  ];

  const filters = [
    'search' => ['quotation_number', 'observations', 'vehicle.customer.full_name', 'client.full_name', 'vehicle.plate'],
    'vehicle_id' => '=',
    'quotation_date' => 'between',
    'is_take' => '=',
    'area_id' => '=',
    'currency_id' => '=',
    'discard_reason_id' => '=',
    'status' => '=',
    'sede_id' => '=',
    'supply_type' => 'in',
    'has_invoice_generated' => '=',
  ];

  const sorts = [
    'id',
    'quotation_number',
    'quotation_date',
    'total_amount',
    'created_at',
  ];

  protected $casts = [
    'quotation_date' => 'datetime',
    'expiration_date' => 'datetime',
    'collection_date' => 'datetime',
    'discarded_at' => 'datetime',
    'has_invoice_generated' => 'boolean',
    'is_fully_paid' => 'boolean',
    'is_requested_by_management' => 'boolean',
  ];

  //STATUS CONSTANTS
  const STATUS_DESCARTADO = 'Descartado';
  const STATUS_APERTURADO = 'Aperturado';
  const STATUS_POR_FACTURAR = 'Por Facturar';
  const STATUS_FACTURADO = 'Facturado';

  // SUPPLY TYPE CONSTANTS
  const STOCK = 'STOCK';
  const LIMA = 'LIMA';
  const IMPORTACION = 'IMPORTACION';

  // DIAS PERMITIDOS PARA EDITAR O ELIMINAR UNA COTIZACION
  const  DAYS_TO_EDIT_OR_DELETE = 15;

  protected static function boot()
  {
    parent::boot();

    // when deleting a quotation, also delete its details
    static::deleting(function ($quotation) {
      $quotation->details()->delete();
    });
  }

  public function setDiscardedNoteAttribute($value)
  {
    $this->attributes['discarded_note'] = strtoupper($value);
  }

  public function setObservationsAttribute($value)
  {
    $this->attributes['observations'] = strtoupper($value);
  }

  public function vehicle(): BelongsTo
  {
    return $this->belongsTo(Vehicles::class, 'vehicle_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function chiefApprovalBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'chief_approval_by');
  }

  public function managerApprovalBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'manager_approval_by');
  }

  public function area(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'area_id');
  }

  public function typeCurrency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
  }

  public function client(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'client_id');
  }

  public function details()
  {
    return $this->hasMany(ApOrderQuotationDetails::class, 'order_quotation_id');
  }

  public function advancesOrderQuotation(): HasMany
  {
    return $this->hasMany(ElectronicDocument::class, 'order_quotation_id');
  }

  public function discardReason(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'discard_reason_id');
  }

  public function discardedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'discarded_by');
  }

  public function discountRequests()
  {
    return $this->hasMany(DiscountRequestsOrderQuotation::class, 'ap_order_quotation_id');
  }

  public function markAsTaken(): void
  {
    $this->is_take = 1;
    $this->save();
  }

  /**
   * Centralized method to calculate and update quotation totals based on details.
   * This method calculates:
   * - subtotal: sum of all items (quantity * unit_price) without discounts
   * - discount_amount: total discount amount in money
   * - discount_percentage: average discount percentage
   * - tax_amount: IGV (18%) calculated on subtotal after discounts
   * - total_amount: final total including discounts and taxes
   *
   * @return void
   */
  public function calculateTotals(): void
  {
    // Get all details for this quotation
    $details = $this->details;

    // Calculate totals
    $subtotal = 0; // suma de (quantity * unit_price) sin descuentos
    $sumTotalAmountItems = 0; // suma de total_amount de cada item (ya con descuento aplicado)

    foreach ($details as $detail) {
      $itemSubtotal = $detail->quantity * $detail->unit_price;
      $subtotal += $itemSubtotal;
      $sumTotalAmountItems += $detail->total_amount;
    }

    // Calculate discount amount (cuánto se descontó en total en dinero)
    $discountAmount = $subtotal - $sumTotalAmountItems;

    // Calculate discount percentage (porcentaje promedio de descuento)
    $discountPercentage = $subtotal > 0 ? ($discountAmount / $subtotal) * 100 : 0;

    // Calculate tax amount (IGV 18% sobre la suma de total_amount de items)
    $taxAmount = $sumTotalAmountItems * (Constants::VAT_TAX / 100);

    // Calculate total amount (suma de total_amount items + IGV)
    $totalAmount = $sumTotalAmountItems + $taxAmount;

    // Update quotation with all calculated values
    $this->subtotal = round($subtotal, 2);
    $this->discount_amount = round($discountAmount, 2);
    $this->discount_percentage = round($discountPercentage, 2);
    $this->tax_amount = round($taxAmount, 2);
    $this->total_amount = round($totalAmount, 2);
  }
}
