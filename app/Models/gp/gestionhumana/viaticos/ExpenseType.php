<?php

namespace App\Models\gp\gestionhumana\viaticos;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseType extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_expense_type';

  protected $fillable = [
    'parent_id',
    'code',
    'name',
    'description',
    'requires_receipt',
    'active',
    'order',
  ];

  protected $casts = [
    'requires_receipt' => 'boolean',
    'active' => 'boolean',
  ];

  const filters = [
    'search' => ['name', 'description', 'code'],
    'active' => '=',
    'parent_id' => '=',
    'requires_receipt' => '=',
  ];

  const sorts = [
    'name',
    'code',
    'order',
    'active',
  ];

  const string ACCOMMODATION = "ACCOMMODATION";
  const string TRANSPORTATION = "TRANSPORTATION";
  const string MEALS = "MEALS";
  const string BREAKFAST = "BREAKFAST";
  const string LUNCH = "LUNCH";
  const string DINNER = "DINNER";
  const string LOCAL_TRANSPORT = "LOCAL_TRANSPORT";
  const int ACCOMMODATION_ID = 1;
  const int TRANSPORTATION_ID = 2;
  const int MEALS_ID = 3;
  const int BREAKFAST_ID = 4;
  const int LUNCH_ID = 5;
  const int DINNER_ID = 6;
  const int LOCAL_TRANSPORT_ID = 7;

  /**
   * Get the parent expense type (self-referencing)
   */
  public function parent(): BelongsTo
  {
    return $this->belongsTo(ExpenseType::class, 'parent_id');
  }

  /**
   * Get all children expense types
   */
  public function children(): HasMany
  {
    return $this->hasMany(ExpenseType::class, 'parent_id')->orderBy('order');
  }

  /**
   * Get all per diem rates for this expense type
   */
  public function perDiemRates(): HasMany
  {
    return $this->hasMany(PerDiemRate::class);
  }

  /**
   * Get all per diem expenses for this expense type
   */
  public function perDiemExpenses(): HasMany
  {
    return $this->hasMany(PerDiemExpense::class);
  }

  /**
   * Get all request budgets for this expense type
   */
  public function requestBudgets(): HasMany
  {
    return $this->hasMany(RequestBudget::class);
  }

  /**
   * Scope to filter active expense types ordered by order field
   */
  public function scopeActive($query)
  {
    return $query->where('active', true)->orderBy('order');
  }

  /**
   * Scope to filter parent expense types (no parent_id)
   */
  public function scopeParents($query)
  {
    return $query->whereNull('parent_id')->orderBy('order');
  }

  /**
   * Scope to filter children expense types (has parent_id)
   */
  public function scopeChildren($query)
  {
    return $query->whereNotNull('parent_id')->orderBy('order');
  }

  /**
   * Check if this expense type is a parent
   */
  public function isParent(): bool
  {
    return $this->parent_id === null;
  }

  /**
   * Check if this expense type is a child
   */
  public function isChild(): bool
  {
    return $this->parent_id !== null;
  }

  /**
   * Check if this expense type has children
   */
  public function hasChildren(): bool
  {
    return $this->children()->count() > 0;
  }

  /**
   * Get the full name with parent if applicable
   */
  public function getFullNameAttribute(): string
  {
    if ($this->parent) {
      return $this->parent->name . ' - ' . $this->name;
    }

    return $this->name;
  }
}
