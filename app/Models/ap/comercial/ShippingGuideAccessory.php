<?php

namespace App\Models\ap\comercial;

use App\Models\ap\maestroGeneral\UnitMeasurement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ShippingGuideAccessory extends Model
{
  use SoftDeletes;

  protected $table = 'ap_shipping_guide_accessories';

  protected $fillable = [
    'shipping_guide_id',
    'description',
    'quantity',
    'unit_measurement_id',
  ];

  public function setDescriptionAttribute($value): void
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function shippingGuide(): BelongsTo
  {
    return $this->belongsTo(ShippingGuides::class, 'shipping_guide_id');
  }

  public function unitMeasurement(): BelongsTo
  {
    return $this->belongsTo(UnitMeasurement::class, 'unit_measurement_id');
  }
}
