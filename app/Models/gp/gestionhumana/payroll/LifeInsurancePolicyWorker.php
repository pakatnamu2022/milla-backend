<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LifeInsurancePolicyWorker extends BaseModel
{
  protected $table = 'gh_life_insurance_policy_workers';

  protected $fillable = [
    'policy_id',
    'worker_id',
    'insured_salary',
    'net_cost',
    'total_with_igv',
    'monthly_amount',
  ];

  protected $casts = [
    'insured_salary' => 'decimal:2',
    'net_cost' => 'decimal:4',
    'total_with_igv' => 'decimal:4',
    'monthly_amount' => 'decimal:4',
  ];

  /**
   * Detalle del trabajador en la póliza de su empresa vigente a la fecha (fin del periodo).
   */
  public static function resolve(int $workerId, int $companyId, $date): ?self
  {
    return static::where('worker_id', $workerId)
      ->whereHas('policy', fn ($q) => $q->where('company_id', $companyId)->validAt($date))
      ->first();
  }

  public function policy(): BelongsTo
  {
    return $this->belongsTo(LifeInsurancePolicy::class, 'policy_id');
  }

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }
}
