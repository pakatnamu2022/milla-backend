<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollWorkTypeSegment extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_payroll_work_type_segments';

  const SEGMENT_TYPE_WORK = 'WORK';
  const SEGMENT_TYPE_BREAK = 'BREAK';

  const TYPES = [
    self::SEGMENT_TYPE_WORK,
    self::SEGMENT_TYPE_BREAK,
  ];

  protected $fillable = [
    'work_type_id',
    'segment_type',
    'segment_order',
    'duration_hours',
    'multiplier',
    'description',
  ];

  protected $casts = [
    'segment_order' => 'integer',
    'duration_hours' => 'decimal:2',
    'multiplier' => 'decimal:4',
  ];

  const filters = [
    'work_type_id' => '=',
    'segment_type' => '=',
  ];

  const sorts = [
    'segment_order',
    'created_at',
  ];

  /**
   * Get the work type this segment belongs to
   */
  public function workType(): BelongsTo
  {
    return $this->belongsTo(PayrollWorkType::class, 'work_type_id');
  }

  /**
   * Scope to get only work segments
   */
  public function scopeWork($query)
  {
    return $query->where('segment_type', self::SEGMENT_TYPE_WORK);
  }

  /**
   * Scope to get only break segments
   */
  public function scopeBreak($query)
  {
    return $query->where('segment_type', self::SEGMENT_TYPE_BREAK);
  }

  /**
   * Scope to order by segment order
   */
  public function scopeOrdered($query)
  {
    return $query->orderBy('segment_order');
  }

  /**
   * Check if this is a work segment
   */
  public function isWork(): bool
  {
    return $this->segment_type === self::SEGMENT_TYPE_WORK;
  }

  /**
   * Check if this is a break segment
   */
  public function isBreak(): bool
  {
    return $this->segment_type === self::SEGMENT_TYPE_BREAK;
  }
}
