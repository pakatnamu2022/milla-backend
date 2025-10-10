<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\gp\maestroGeneral\ExchangeRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PurchaseRequestQuote extends Model
{
  use SoftDeletes;

  protected $table = 'purchase_request_quote';

  protected $fillable = [
    'type_document',
    'type_vehicle',
    'quote_deadline',
    'exchange_rate_id',
    'subtotal',
    'total',
    'comment',
    'opportunity_id',
    'holder_id',
    'vehicle_color_id',
    'ap_models_vn_id',
    'vehicle_vn_id',
    'doc_type_currency_id',
  ];

  const filters = [
    'type_document' => '=',
    'type_vehicle' => '=',
    'quote_deadline' => '=',
    'exchange_rate_id' => '=',
    'subtotal' => '=',
    'total' => '=',
    'opportunity_id' => '=',
    'holder_id' => '=',
    'vehicle_color_id' => '=',
    'ap_models_vn_id' => '=',
    'vehicle_vn_id' => '=',
    'doc_type_currency_id' => '=',
  ];

  const sorts = [
    'id',
    'created_at',
    'updated_at',
  ];

  public function setCommentAttribute($value): void
  {
    if ($value) {
      $this->attributes['comment'] = Str::upper($value);
    }
  }

  public function discountCoupons()
  {
    return $this->hasMany(DiscountCoupons::class, 'purchase_request_quote_id');
  }

  public function vehicleColor(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'vehicle_color_id');
  }

  public function docTypeCurrency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'doc_type_currency_id');
  }

  public function apModelsVn(): BelongsTo
  {
    return $this->belongsTo(ApModelsVn::class, 'ap_models_vn_id');
  }

  public function vehicleVn(): BelongsTo
  {
    return $this->belongsTo(VehicleVn::class, 'vehicle_vn_id');
  }

  public function oportunity(): BelongsTo
  {
    return $this->belongsTo(Opportunity::class, 'opportunity_id');
  }

  public function holder(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'holder_id');
  }

  public function exchangeRate(): BelongsTo
  {
    return $this->belongsTo(ExchangeRate::class, 'exchange_rate_id');
  }
}
