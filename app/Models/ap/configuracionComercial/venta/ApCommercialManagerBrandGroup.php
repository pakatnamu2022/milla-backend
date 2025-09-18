<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\ap\ApCommercialMasters;
use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApCommercialManagerBrandGroup extends Model
{
  use SoftDeletes;

  protected $table = 'ap_commercial_manager_brand_group_periods';

  protected $fillable = [
    'brand_group_id',
    'commercial_manager_id',
    'year',
    'month',
    'status',
  ];

  const filters = [
    'search' => ['brand_group_id', 'commercial_manager_id'],
    'brand_group_id' => '=',
    'commercial_manager_id' => '=',
    'year' => '=',
    'month' => '=',
  ];

  const sorts = [
    'brand_group_id',
    'commercial_manager_id',
    'year',
    'month',
  ];

  public function commercialManager()
  {
    return $this->belongsTo(Person::class, 'commercial_manager_id');
  }

  public function brandGroup()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'brand_group_id');
  }
}
