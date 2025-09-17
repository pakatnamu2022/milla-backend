<?php

namespace App\Models\gp\maestroGeneral;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Area;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\gestionsistema\Department;
use App\Models\gp\gestionsistema\District;
use App\Models\gp\gestionsistema\Person;
use App\Models\gp\gestionsistema\Province;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sede extends BaseModel
{
  use SoftDeletes;

  protected $table = 'config_sede';
  public $timestamps = false;

  protected $fillable = [
    'localidad',
    'suc_abrev',
    'abreviatura',
    'empresa_id',
    'ruc',
    'razon_social',
    'direccion',
    'distrito',
    'provincia',
    'departamento',
    'web',
    'email',
    'logo',
    'ciudad',
    'info_labores',
    'dyn_code',
    'establishment',
    'department_id',
    'province_id',
    'district_id',
    'status'
  ];

  const filters = [
    'id' => '=',
    'search' => ['suc_abrev', 'abreviatura', 'razon_social', 'direccion', 'ciudad', 'dyn_code', 'establishment'],
    'empresa_id' => '=',
    'department_id' => '=',
    'province_id' => '=',
    'district_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'id',
    'suc_abrev',
    'abreviatura',
    'razon_social',
    'direccion',
    'ciudad',
  ];

  public function areas()
  {
    //return $this->belongsTo(Area::class);
    return $this->hasMany(Area::class, 'sede_id', 'id');
  }

  public function company()
  {
    return $this->belongsTo(Company::class, 'empresa_id', 'id');
  }

  public function workers()
  {
    return $this->belongsToMany(Person::class, 'ap_assign_company_branch_period', 'sede_id', 'worker_id')
      ->withTimestamps();
  }

  public function department()
  {
    return $this->belongsTo(Department::class, 'department_id', 'id');
  }

  public function province()
  {
    return $this->belongsTo(Province::class, 'province_id', 'id');
  }

  public function district()
  {
    return $this->belongsTo(District::class, 'district_id', 'id');
  }
}
