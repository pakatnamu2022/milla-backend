<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\gp\gestionsistema\CompanyBranch;
use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApAssignCompanyBranchPeriod extends Model
{
  use softDeletes;

  protected $table = "ap_assign_company_branch_period";

  protected $fillable = [
    'company_branch_id',
    'worker_id',
    'year',
    'month',
  ];

  const filters = [
    'search' => ['company_branch_id', 'worker_id'],
    'company_branch_id' => '=',
    'worker_id' => '=',
    'year' => '=',
    'month' => '=',
  ];

  const sorts = [
    'company_branch_id',
    'worker_id',
    'year',
    'month',
  ];

  public function companyBranch()
  {
    return $this->belongsTo(CompanyBranch::class, 'company_branch_id');
  }

  public function worker()
  {
    return $this->belongsTo(Person::class, 'worker_id');
  }
}
