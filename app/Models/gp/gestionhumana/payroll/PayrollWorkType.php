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
    'multiplier',
    'base_hours',
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
}
