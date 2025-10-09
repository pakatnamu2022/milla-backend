<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApCommercialMasters;
use Illuminate\Database\Eloquent\Model;
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
    'exchange_rate',
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
    'exchange_rate' => '=',
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

  public function vehicleColor()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'vehicle_color_id');
  }

  public function docTypeCurrency()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'doc_type_currency_id');
  }

  public function apModelsVn()
  {
    return $this->belongsTo(ApModelsVn::class, 'ap_models_vn_id');
  }

  public function vehicleVn()
  {
    return $this->belongsTo(VehicleVn::class, 'vehicle_vn_id');
  }

  public function oportunity()
  {
    return $this->belongsTo(Oportunity::class, 'opportunity_id');
  }
}
