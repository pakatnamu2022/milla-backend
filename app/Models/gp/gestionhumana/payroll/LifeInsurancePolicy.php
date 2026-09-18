<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LifeInsurancePolicy extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_life_insurance_policies';

  protected $fillable = [
    'company_id',
    'insurer',
    'policy_number',
    'start_date',
    'end_date',
    'days',
    'monthly_rate',
    'igv_rate',
    'exclusion',
    'total_insured_salary',
    'net_premium',
    'created_by',
  ];

  protected $casts = [
    'start_date' => 'date:Y-m-d',
    'end_date' => 'date:Y-m-d',
    'monthly_rate' => 'decimal:6',
    'igv_rate' => 'decimal:4',
    'exclusion' => 'decimal:2',
    'total_insured_salary' => 'decimal:2',
    'net_premium' => 'decimal:4',
  ];

  const filters = [
    'company_id' => '=',
    'search' => ['insurer', 'policy_number'],
  ];

  const sorts = [
    'start_date',
    'created_at',
  ];

  public function scopeValidAt($query, $date)
  {
    $date = $date instanceof Carbon ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');

    return $query->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date);
  }

  /** Meses facturados por la póliza: 12 x (días de vigencia / 365). */
  public function billedMonths(): float
  {
    return 12 * ($this->days / 365);
  }

  /** Monto mensual con IGV de un sueldo asegurado, con la prima efectiva de esta póliza. */
  public function monthlyAmountFor(float $insuredSalary): array
  {
    $netCost = $this->total_insured_salary > 0
      ? (float)$this->net_premium * $insuredSalary / (float)$this->total_insured_salary
      : 0.0;
    $totalWithIgv = $netCost * (1 + (float)$this->igv_rate);

    return [
      'net_cost' => $netCost,
      'total_with_igv' => $totalWithIgv,
      'monthly_amount' => $totalWithIgv / $this->billedMonths(),
    ];
  }

  public function company(): BelongsTo
  {
    return $this->belongsTo(\App\Models\gp\gestionsistema\Company::class, 'company_id');
  }

  public function workers(): HasMany
  {
    return $this->hasMany(LifeInsurancePolicyWorker::class, 'policy_id');
  }
}
