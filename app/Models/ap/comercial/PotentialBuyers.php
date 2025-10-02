<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    'full_name',
    'phone',
    'email',
    'campaign',
    'worker_id',
    'sede_id',
    'vehicle_brand_id',
    'document_type_id',
    'type',
    'income_sector_id',
    'area_id',
    'status_num_doc',
    'use'
  ];

  const filters = [
    'search' => ['full_name', 'worker.nombre_completo', 'sede.abreviatura', 'vehicleBrand.name', 'num_doc', 'email', 'phone', 'campaign'],
    'sede_id' => '=',
    'vehicle_brand_id' => '=',
    'document_type_id' => '=',
    'registration_date' => 'between',
    'type' => '=',
    'income_sector_id' => '=',
    'area_id' => '=',
    'worker_id' => '=',
    'status_num_doc' => '=',
    'use' => '=',
  ];

  const sorts = [
    'registration_date',
    'full_name',
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

  public function setFullNameAttribute($value): void
  {
    $this->attributes['full_name'] = Str::upper($value);
  }

  public function setCampaignAttribute($value): void
  {
    $this->attributes['campaign'] = Str::upper($value);
  }

  public function setTypeAttribute($value): void
  {
    $this->attributes['type'] = Str::upper($value);
  }

  public function vehicleBrand(): BelongsTo
  {
    return $this->belongsTo(ApVehicleBrand::class, 'vehicle_brand_id');
  }

  public function documentType(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'document_type_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function area(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'area_id');
  }

  public function incomeSector(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'income_sector_id');
  }

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }
}
