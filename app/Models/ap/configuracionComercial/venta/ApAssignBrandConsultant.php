<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\CompanyBranch;
use App\Models\gp\gestionsistema\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApAssignBrandConsultant extends Model
{
  use SoftDeletes;

  protected $table = 'ap_assign_brand_consultant';

  protected $fillable = [
    'sales_target',
    'year',
    'month',
    'status',
    'brand_id',
    'worker_id',
    'company_branch_id',
    'sede_id', //temporal
  ];

  const filters = [
    'search' => ['brand.name', 'worker.nombre_completo', 'sede.abreviatura'],
    'year' => '=',
    'month' => '=',
    'status' => '=',
    'brand_id' => '=',
    'company_branch_id' => '=',
    'sede_id' => '=', //temporal
  ];

  const sorts = [
    'id',
    'year',
    'month',
    'status',
    'brand_id',
    'worker_id',
    'company_branch_id',
    'sede_id', //temporal
  ];

  public function brand()
  {
    return $this->belongsTo(ApVehicleBrand::class, 'brand_id');
  }

  public function worker()
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function companyBranch()
  {
    return $this->belongsTo(CompanyBranch::class, 'company_branch_id');
  }

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }
}
