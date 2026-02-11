<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollWorkType extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_payroll_work_types';

  protected $fillable = [
    'code',
    'name',
    'description',
    'shift_type',
    'multiplier',
    'base_hours',
    'shift_start_time',
    'shift_duration_hours',
    'nocturnal_base_multiplier',
    'is_extra_hours',
    'is_night_shift',
    'is_holiday',
    'is_sunday',
    'active',
    'order',
  ];

  protected $casts = [
    'multiplier' => 'decimal:4',
    'base_hours' => 'integer',
    'shift_start_time' => 'datetime',
    'shift_duration_hours' => 'decimal:2',
    'nocturnal_base_multiplier' => 'decimal:4',
    'is_extra_hours' => 'boolean',
    'is_night_shift' => 'boolean',
    'is_holiday' => 'boolean',
    'is_sunday' => 'boolean',
    'active' => 'boolean',
    'order' => 'integer',
  ];

  const filters = [
    'search' => ['code', 'name'],
    'code' => '=',
    'shift_type' => '=',
    'active' => '=',
    'is_extra_hours' => '=',
    'is_night_shift' => '=',
    'is_holiday' => '=',
    'is_sunday' => '=',
  ];

  const sorts = [
    'code',
    'name',
    'order',
    'created_at',
  ];

  /**
   * Get all schedules for this work type
   */
  public function schedules(): HasMany
  {
    return $this->hasMany(PayrollSchedule::class, 'work_type_id');
  }

  /**
   * Scope to get only active work types
   */
  public function scopeActive($query)
  {
    return $query->where('active', true);
  }

  /**
   * Scope to get extra hours types
   */
  public function scopeExtraHours($query)
  {
    return $query->where('is_extra_hours', true);
  }

  /**
   * Scope to get night shift types
   */
  public function scopeNightShift($query)
  {
    return $query->where('is_night_shift', true);
  }

  /**
   * Get all segments for this work type
   */
  public function segments(): HasMany
  {
    return $this->hasMany(PayrollWorkTypeSegment::class, 'work_type_id')->ordered();
  }

  /**
   * Get total shift duration from segments
   */
  public function getTotalSegmentDuration(): float
  {
    return $this->segments->sum('duration_hours');
  }
}
