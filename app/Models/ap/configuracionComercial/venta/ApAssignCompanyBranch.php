<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\gp\gestionsistema\Person;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApAssignCompanyBranch extends Model
{
  use softDeletes;

  protected $table = "ap_assign_company_branch_period";

  protected $fillable = [
    'sede_id',
    'worker_id',
    'year',
    'month',
    'status',
  ];

  const filters = [
    'search' => ['worker_id'],
    'sede_id' => '=',
    'worker_id' => '=',
    'year' => '=',
    'month' => '=',
  ];

  const sorts = [
    'worker_id',
    'year',
    'month',
  ];

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Person::class, 'worker_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }
}
