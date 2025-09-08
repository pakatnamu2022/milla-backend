<?php

namespace App\Models\gp\gestionsistema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CompanyBranch extends Model
{
  protected $table = 'company_branch';

  protected $fillable = [
    'name',
    'abbreviation',
    'address',
    'company_id',
    'district_id',
    'province_id',
    'department_id',
    'status',
  ];

  const filters = [
    'search' => ['name', 'abbreviation', 'address'],
    'company_id',
    'district_id',
    'province_id',
    'department_id',
    'status',
  ];

  const sorts = [
    'id',
    'name',
    'abbreviation',
    'address',
    'company_id',
    'district_id',
    'province_id',
    'department_id',
  ];

  public function setNameAttribute($value)
  {
    $this->attributes['name'] = Str::upper(Str::ascii($value));
  }

  public function setAbbreviationAttribute($value)
  {
    $this->attributes['abbreviation'] = Str::upper(Str::ascii($value));
  }

  public function setAddressAttribute($value)
  {
    $this->attributes['address'] = Str::upper(Str::ascii($value));
  }

  public function company()
  {
    return $this->belongsTo(Company::class, 'company_id');
  }

  public function district()
  {
    return $this->belongsTo(District::class, 'district_id');
  }

  public function province()
  {
    return $this->belongsTo(Province::class, 'province_id');
  }

  public function department()
  {
    return $this->belongsTo(Department::class, 'department_id');
  }

  public function workers()
  {
    return $this->belongsToMany(Person::class, 'ap_assign_company_branch', 'company_branch_id', 'worker_id')
      ->withTimestamps();
  }
}
