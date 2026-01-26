<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollFormulaVariable extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_payroll_formula_variables';

  protected $fillable = [
    'code',
    'name',
    'description',
    'type',
    'value',
    'source_field',
    'formula',
    'active',
  ];

  protected $casts = [
    'value' => 'decimal:4',
    'active' => 'boolean',
  ];

  const TYPE_FIXED = 'FIXED';
  const TYPE_SYSTEM = 'SYSTEM';
  const TYPE_CALCULATED = 'CALCULATED';

  const TYPES = [
    self::TYPE_FIXED,
    self::TYPE_SYSTEM,
    self::TYPE_CALCULATED,
  ];

  const filters = [
    'search' => ['code', 'name'],
    'code' => '=',
    'type' => '=',
    'active' => '=',
  ];

  const sorts = [
    'code',
    'name',
    'type',
    'created_at',
  ];

  /**
   * Scope to get only active variables
   */
  public function scopeActive($query)
  {
    return $query->where('active', true);
  }

  /**
   * Scope to get fixed type variables
   */
  public function scopeFixed($query)
  {
    return $query->where('type', self::TYPE_FIXED);
  }

  /**
   * Scope to get system type variables
   */
  public function scopeSystem($query)
  {
    return $query->where('type', self::TYPE_SYSTEM);
  }

  /**
   * Scope to get calculated type variables
   */
  public function scopeCalculated($query)
  {
    return $query->where('type', self::TYPE_CALCULATED);
  }

  /**
   * Get all active variables as key-value array
   */
  public static function getActiveVariablesArray(): array
  {
    return self::active()
      ->fixed()
      ->pluck('value', 'code')
      ->toArray();
  }
}
