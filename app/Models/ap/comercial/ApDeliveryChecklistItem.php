<?php

namespace App\Models\ap\comercial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApDeliveryChecklistItem extends Model
{
  use SoftDeletes;

  protected $table = 'ap_delivery_checklist_item';

  protected $fillable = [
    'delivery_checklist_id',
    'source',
    'source_id',
    'description',
    'quantity',
    'unit',
    'is_confirmed',
    'observations',
    'sort_order',
  ];

  protected $casts = [
    'is_confirmed' => 'boolean',
    'quantity' => 'decimal:2',
  ];

  const SOURCE_RECEPTION = 'reception';
  const SOURCE_PURCHASE_ORDER = 'purchase_order';
  const SOURCE_MANUAL = 'manual';

  public function checklist(): BelongsTo
  {
    return $this->belongsTo(ApDeliveryChecklist::class, 'delivery_checklist_id');
  }

  public function setDescriptionAttribute($value): void
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function setObservationsAttribute($value): void
  {
    $this->attributes['observations'] = $value ? Str::upper(Str::ascii($value)) : null;
  }
}
