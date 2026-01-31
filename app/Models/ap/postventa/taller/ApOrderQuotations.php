<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\TypeCurrency;
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

  public function Area(): BelongsTo
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

  public function markAsTaken(): void
  {
    $this->is_take = 1;
    $this->save();
  }
}
