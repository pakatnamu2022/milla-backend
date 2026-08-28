<?php

namespace App\Models\ap\postventa\repuestos;

use App\Models\ap\ApMasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovedAccessoryPrice extends Model
{
  protected $table = 'approved_accessory_prices';

  protected $fillable = [
    'approved_accessory_id',
    'body_type_id',
    'price',
  ];

  protected $casts = [
    'price' => 'decimal:2',
  ];

  public function approvedAccessory(): BelongsTo
  {
    return $this->belongsTo(ApprovedAccessories::class, 'approved_accessory_id');
  }

  public function bodyType(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'body_type_id');
  }
}
