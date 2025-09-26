<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PotentialBuyers extends Model
{
  use SoftDeletes;

  protected $table = 'potential_buyers';

  protected $fillable = [
    'registration_date',
    'model',
    'version',
    'num_doc',
    'name',
    'surnames',
    'phone',
    'email',
    'campaign',
    'sede_id',
    'vehicle_brand_id',
    'document_type_id',
    'type',
    'income_sector_id',
    'area_id',
  ];

  const filters = [
    'search' => ['name', 'num_doc', 'email', 'phone', 'campaign'],
    'sede_id' => '=',
    'vehicle_brand_id' => '=',
    'document_type_id' => '=',
    'registration_date_from' => 'registration_date>=',
    'registration_date_to' => 'registration_date<=',
    'type' => '=',
    'income_sector_id' => '=',
    'area_id' => '=',
  ];

  const sorts = [
    'registration_date',
    'name',
    'num_doc',
    'email',
    'phone',
    'campaign',
    'sede_id',
    'vehicle_brand_id',
  ];

  public function setModelAttribute($value): void
  {
    $this->attributes['model'] = Str::upper($value);
  }

  public function setVersionAttribute($value): void
  {
    $this->attributes['version'] = Str::upper($value);
  }

  public function setNameAttribute($value): void
  {
    $this->attributes['name'] = Str::upper($value);
  }

  public function setSurnamesAttribute($value): void
  {
    if ($value) {
      $this->attributes['surnames'] = Str::upper($value);
    }
  }

  public function setCampaignAttribute($value): void
  {
    $this->attributes['campaign'] = Str::upper($value);
  }

  public function setTypeAttribute($value): void
  {
    $this->attributes['type'] = Str::upper($value);
  }

  public function vehicleBrand()
  {
    return $this->belongsTo(ApVehicleBrand::class, 'vehicle_brand_id');
  }

  public function documentType()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'document_type_id');
  }

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function area()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'area_id');
  }

  public function incomeSector()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'income_sector_id');
  }
}
