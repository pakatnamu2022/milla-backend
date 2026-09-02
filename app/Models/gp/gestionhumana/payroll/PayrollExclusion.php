<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollExclusion extends BaseModel
{
  protected $table = 'gh_payroll_exclusions';

  // Conceptos soportados. Se agregan aquí (y donde se calcule el concepto) según se generalice
  // el uso de esta tabla — ver PayrollLiquidationBbssService::computeWorkerBase().
  const string CONCEPT_FAMILY_ALLOWANCE = 'FAMILY_ALLOWANCE';

  const array CONCEPTS = [
    self::CONCEPT_FAMILY_ALLOWANCE,
  ];

  protected $fillable = [
    'worker_id',
    'period_id',
    'concept',
    'reason',
    'created_by',
  ];

  const filters = [
    'search' => ['worker.nombre_completo', 'worker.vat', 'reason'],
    'worker_id' => '=',
    'period_id' => '=',
    'concept' => '=',
    'period.company_id' => '=',
  ];

  const sorts = [
    'worker_id',
    'period_id',
    'concept',
    'created_at',
  ];

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function period(): BelongsTo
  {
    return $this->belongsTo(PayrollPeriod::class, 'period_id');
  }
}
