<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollCalculationDetail extends BaseModel
{
  protected $table = 'gh_payroll_calculation_details';

  protected $fillable = [
    'calculation_id',
    'concept_id',
    'concept_code',
    'concept_name',
    'type',
    'category',
    'hour_type',
    'hours',
    'days_worked',
    'multiplier',
    'use_shift',
    'base_amount',
    'rate',
    'hour_value',
    'amount',
    'formula_used',
    'variables_snapshot',
    'calculation_order',
  ];

  protected $casts = [
    'hours' => 'decimal:2',
    'days_worked' => 'integer',
    'multiplier' => 'decimal:4',
    'use_shift' => 'boolean',
    'base_amount' => 'decimal:2',
    'rate' => 'decimal:4',
    'hour_value' => 'decimal:2',
    'amount' => 'decimal:2',
    'variables_snapshot' => 'array',
    'calculation_order' => 'integer',
  ];

  const filters = [
    'calculation_id' => '=',
    'concept_id' => '=',
    'type' => '=',
  ];

  const sorts = [
    'calculation_order',
    'final_amount',
  ];

  /**
   * Get the calculation for this detail
   */
  public function calculation(): BelongsTo
  {
    return $this->belongsTo(PayrollCalculation::class, 'calculation_id');
  }

  /**
   * Get the concept for this detail
   */
  public function concept(): BelongsTo
  {
    return $this->belongsTo(PayrollConcept::class, 'concept_id');
  }

  /**
   * Scope to get earnings
   */
  public function scopeEarnings($query)
  {
    return $query->where('type', PayrollConcept::TYPE_EARNING);
  }

  /**
   * Scope to get deductions
   */
  public function scopeDeductions($query)
  {
    return $query->where('type', PayrollConcept::TYPE_DEDUCTION);
  }

  /**
   * Scope to get employer contributions
   */
  public function scopeEmployerContributions($query)
  {
    return $query->where('type', PayrollConcept::TYPE_EMPLOYER_CONTRIBUTION);
  }
}
