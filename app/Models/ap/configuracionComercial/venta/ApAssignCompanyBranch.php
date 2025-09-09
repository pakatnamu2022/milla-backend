<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\gp\gestionsistema\CompanyBranch;
use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApAssignCompanyBranch extends Model
{
  use SoftDeletes;

  protected $table = "ap_assign_company_branch";

  protected $fillable = [
    'company_branch_id',
    'sede_id',
    'worker_id',
  ];

  public function companyBranch()
  {
    return $this->belongsTo(CompanyBranch::class, 'company_branch_id');
  }

  public function worker()
  {
    return $this->belongsTo(Person::class, 'worker_id');
  }

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }
}
