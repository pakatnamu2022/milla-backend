<?php

namespace App\Models\gp\gestionhumana\viaticos;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\District;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class PerDiemRate extends BaseModel
{
  use SoftDeletes;

  protected $fillable = [
    'per_diem_policy_id',
    'district_id',
    'per_diem_category_id',
    'expense_type_id',
    'daily_amount',
    'active',
  ];

  protected $casts = [
    'daily_amount' => 'decimal:2',
    'active' => 'boolean',
  ];

  /**
   * Get the policy this rate belongs to
   */
  public function policy(): BelongsTo
  {
    return $this->belongsTo(PerDiemPolicy::class, 'per_diem_policy_id');
  }

  /**
   * Get the district this rate applies to
   */
  public function district(): BelongsTo
  {
    return $this->belongsTo(District::class);
  }

  /**
   * Get the category this rate applies to
   */
  public function category(): BelongsTo
  {
    return $this->belongsTo(PerDiemCategory::class, 'per_diem_category_id');
  }

  /**
   * Get the expense type this rate applies to
   */
  public function expenseType(): BelongsTo
  {
    return $this->belongsTo(ExpenseType::class);
  }

  /**
   * Scope to filter active rates
   */
  public function scopeActive($query)
  {
    return $query->where('active', true);
  }

  /**
   * Scope to filter rates by district
   */
  public function scopeByDistrict($query, $districtId)
  {
    return $query->where('district_id', $districtId);
  }

  /**
   * Scope to filter rates by category
   */
  public function scopeByCategory($query, $categoryId)
  {
    return $query->where('per_diem_category_id', $categoryId);
  }

  /**
   * Get current rates for a specific district and category (only parent expense types)
   */
  public static function getCurrentRatesByDistrict(int $districtId, int $categoryId): Collection
  {
    return self::with(['policy', 'district', 'category', 'expenseType'])
      ->whereHas('policy', function ($query) {
        $query->where('is_current', true);
      })
      ->whereHas('expenseType', function ($query) {
        $query->whereNull('parent_id');
      })
      ->where('district_id', $districtId)
      ->where('per_diem_category_id', $categoryId)
      ->where('active', true)
      ->get();
  }

  /**
   * Get the destination name (district full name)
   */
  public function getDestinationNameAttribute(): string
  {
    return $this->district->full_name;
  }
}
