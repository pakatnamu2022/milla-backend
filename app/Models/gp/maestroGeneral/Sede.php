<?php

namespace App\Models\gp\maestroGeneral;

use App\Models\ap\ApCommercialMasters;
use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Area;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\gestionsistema\Department;
use App\Models\gp\gestionsistema\District;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\Province;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
    'status',
    'has_workshop',
  ];

  const filters = [
    'id' => '=',
    'search' => ['suc_abrev', 'abreviatura', 'razon_social', 'direccion', 'ciudad', 'dyn_code', 'establishment'],
    'empresa_id' => '=',
    'department_id' => '=',
    'province_id' => '=',
    'district_id' => '=',
    'status' => '=',
    'has_workshop' => '=',
  ];

  const sorts = [
    'id',
    'suc_abrev',
    'abreviatura',
    'razon_social',
    'direccion',
    'ciudad',
  ];

  protected $casts = [
    'has_workshop' => 'boolean',
  ];

  public function setSucAbrevAttribute($value): void
  {
    $this->attributes['suc_abrev'] = Str::upper(Str::ascii($value));
  }

  public function setAbreviaturaAttribute($value): void
  {
    $this->attributes['abreviatura'] = Str::upper(Str::ascii($value));
  }

  public function setDireccionAttribute($value): void
  {
    $this->attributes['direccion'] = Str::upper(Str::ascii($value));
  }

  public function setDynCodeAttribute($value): void
  {
    $this->attributes['dyn_code'] = Str::upper(Str::ascii($value));
  }

  public function setEstablishmentAttribute($value): void
  {
    $this->attributes['establishment'] = Str::upper(Str::ascii($value));
  }

  public function areas()
  {
    //return $this->belongsTo(Area::class);
    return $this->hasMany(Area::class, 'sede_id', 'id');
  }

  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class, 'empresa_id', 'id');
  }

  public function workers(): BelongsToMany
  {
    return $this->belongsToMany(Worker::class, 'ap_assign_company_branch_period', 'sede_id', 'worker_id')
      ->withTimestamps();
  }

  public function department(): BelongsTo
  {
    return $this->belongsTo(Department::class, 'department_id', 'id');
  }

  public function province(): BelongsTo
  {
    return $this->belongsTo(Province::class, 'province_id', 'id');
  }

  public function district(): BelongsTo
  {
    return $this->belongsTo(District::class, 'district_id', 'id');
  }

  public function shop(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'shop_id');
  }
}
