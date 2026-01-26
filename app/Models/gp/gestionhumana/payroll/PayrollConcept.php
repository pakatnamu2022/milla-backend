<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollConcept extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_payroll_concepts';

  protected $fillable = [
    'code',
    'name',
    'description',
    'type',
    'category',
    'formula',
    'formula_description',
    'is_taxable',
    'calculation_order',
    'active',
  ];

  protected $casts = [
    'is_taxable' => 'boolean',
    'calculation_order' => 'integer',
    'active' => 'boolean',
  ];

  // Concept types
  const TYPE_EARNING = 'EARNING';
  const TYPE_DEDUCTION = 'DEDUCTION';
  const TYPE_EMPLOYER_CONTRIBUTION = 'EMPLOYER_CONTRIBUTION';
  const TYPE_INFO = 'INFO';

  const TYPES = [
    self::TYPE_EARNING,
    self::TYPE_DEDUCTION,
    self::TYPE_EMPLOYER_CONTRIBUTION,
    self::TYPE_INFO,
  ];

  // Concept categories
  const CATEGORY_BASE_SALARY = 'BASE_SALARY';
  const CATEGORY_OVERTIME = 'OVERTIME';
  const CATEGORY_BONUSES = 'BONUSES';
  const CATEGORY_ALLOWANCES = 'ALLOWANCES';
  const CATEGORY_COMMISSIONS = 'COMMISSIONS';
  const CATEGORY_SOCIAL_SECURITY = 'SOCIAL_SECURITY';
  const CATEGORY_TAXES = 'TAXES';
  const CATEGORY_LOANS = 'LOANS';
  const CATEGORY_OTHER_DEDUCTIONS = 'OTHER_DEDUCTIONS';
  const CATEGORY_OTHER_EARNINGS = 'OTHER_EARNINGS';
  const CATEGORY_EMPLOYER_TAXES = 'EMPLOYER_TAXES';
  const CATEGORY_INFORMATIVE = 'INFORMATIVE';

  const CATEGORIES = [
    self::CATEGORY_BASE_SALARY,
    self::CATEGORY_OVERTIME,
    self::CATEGORY_BONUSES,
    self::CATEGORY_ALLOWANCES,
    self::CATEGORY_COMMISSIONS,
    self::CATEGORY_SOCIAL_SECURITY,
    self::CATEGORY_TAXES,
    self::CATEGORY_LOANS,
    self::CATEGORY_OTHER_DEDUCTIONS,
    self::CATEGORY_OTHER_EARNINGS,
    self::CATEGORY_EMPLOYER_TAXES,
    self::CATEGORY_INFORMATIVE,
  ];

  const filters = [
    'search' => ['code', 'name'],
    'code' => '=',
    'type' => '=',
    'category' => '=',
    'is_taxable' => '=',
    'active' => '=',
  ];

  const sorts = [
    'code',
    'name',
    'type',
    'category',
    'calculation_order',
    'created_at',
  ];

  /**
   * Get all calculation details for this concept
   */
  public function calculationDetails(): HasMany
  {
    return $this->hasMany(PayrollCalculationDetail::class, 'concept_id');
  }

  /**
   * Scope to get only active concepts
   */
  public function scopeActive($query)
  {
    return $query->where('active', true);
  }

  /**
   * Scope to get earnings
   */
  public function scopeEarnings($query)
  {
    return $query->where('type', self::TYPE_EARNING);
  }

  /**
   * Scope to get deductions
   */
  public function scopeDeductions($query)
  {
    return $query->where('type', self::TYPE_DEDUCTION);
  }

  /**
   * Scope to get employer contributions
   */
  public function scopeEmployerContributions($query)
  {
    return $query->where('type', self::TYPE_EMPLOYER_CONTRIBUTION);
  }

  /**
   * Scope to order by calculation order
   */
  public function scopeOrdered($query)
  {
    return $query->orderBy('calculation_order');
  }
}
