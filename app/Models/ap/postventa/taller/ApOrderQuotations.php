<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\comercial\Vehicles;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApOrderQuotations extends Model
{
  use softDeletes;

  protected $table = 'ap_order_quotations';

  protected $fillable = [
    'vehicle_id',
    'quotation_number',
    'subtotal',
    'discount_percentage',
    'discount_amount',
    'tax_amount',
    'total_amount',
    'validity_days',
    'quotation_date',
    'expiration_date',
    'observations',
    'created_by',
    'is_take',
  ];

  const filters = [
    'search' => ['quotation_number', 'observations'],
    'vehicle_id' => '=',
    'quotation_date' => 'between',
    'is_take' => '=',
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
  ];

  protected static function boot()
  {
    parent::boot();

    // when deleting a quotation, also delete its details
    static::deleting(function ($quotation) {
      $quotation->details()->delete();
    });
  }

  public function setObservationsAttribute($value)
  {
    $this->attributes['observations'] = strtoupper($value);
  }

  public function vehicle(): BelongsTo
  {
    return $this->belongsTo(Vehicles::class, 'vehicle_id');
  }

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function details()
  {
    return $this->hasMany(ApOrderQuotationDetails::class, 'order_quotation_id');
  }
}
