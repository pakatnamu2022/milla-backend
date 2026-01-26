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
    'formula_used',
    'variables_snapshot',
    'calculated_amount',
    'final_amount',
    'calculation_order',
  ];

  protected $casts = [
    'variables_snapshot' => 'array',
    'calculated_amount' => 'decimal:2',
    'final_amount' => 'decimal:2',
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
