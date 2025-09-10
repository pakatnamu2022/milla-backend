<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\ap\configuracionComercial\vehiculo\ApCommercialMasters;
use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApCommercialManagerBrandGroup extends Model
{
  use SoftDeletes;

  protected $table = 'ap_commercial_manager_brand_group';

  protected $fillable = [
    'brand_group_id',
    'commercial_manager_id',
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
