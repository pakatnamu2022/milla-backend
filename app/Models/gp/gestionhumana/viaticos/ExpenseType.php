<?php

namespace App\Models\gp\gestionhumana\viaticos;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseType extends BaseModel
{
  use SoftDeletes;

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
