<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\ApMasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApWorkOrderItem extends Model
{
  use SoftDeletes;

  protected $table = 'ap_work_order_items';

  protected $fillable = [
    'group_number',
    'work_order_id',
    'type_planning_id',
    'type_operation_id',
    'description',
  ];

  const filters = [
    'search' => ['description'],
    'group_number' => '=',
    'work_order_id' => '=',
    'type_planning_id' => '=',
  ];

  const sorts = [
    'id',
    'group_number',
    'created_at',
  ];

  // Mutators
  public function setDescriptionAttribute($value): void
  {
    if ($value) {
      $this->attributes['description'] = Str::upper($value);
    }
  }

  // Relations
  public function workOrder(): BelongsTo
  {
    return $this->belongsTo(ApWorkOrder::class, 'work_order_id');
  }

  public function typePlanning(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'type_planning_id');
  }

  public function typeOperation(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'type_operation_id');
  }
}
