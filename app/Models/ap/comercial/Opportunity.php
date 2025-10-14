<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApFamilies;
use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
  use SoftDeletes;

  protected $table = 'ap_opportunity';

  protected $fillable = [
    'worker_id',
    'client_id',
    'family_id',
    'opportunity_type_id',
    'client_status_id',
    'opportunity_status_id',
    'lead_id',
    'comment',
  ];

  const filters = [
    'worker_id' => '=',
    'client_id' => '=',
    'family_id' => '=',
    'opportunity_type_id' => '=',
    'client_status_id' => '=',
    'opportunity_status_id' => '=',
  ];

  const sorts = [
    'id',
    'created_at',
    'updated_at',
  ];

  const CLOSED = 'CLOSED';
  const SOLD = 'SOLD';
  const COLD = 'COLD';
  const WARM = 'WARM';
  const HOT = 'HOT';

  const OPEN_STATUS_CODES = [self::COLD, self::WARM, self::HOT];

  public function getIsClosedAttribute()
  {
    return !in_array($this->opportunityStatus->code, self::OPEN_STATUS_CODES);
  }

  public function worker()
  {
    return $this->belongsTo(Person::class, 'worker_id');
  }

  public function client()
  {
    return $this->belongsTo(BusinessPartners::class, 'client_id');
  }

  public function family()
  {
    return $this->belongsTo(ApFamilies::class, 'family_id');
  }

  public function lead()
  {
    return $this->belongsTo(PotentialBuyers::class, 'lead_id');
  }

  public function opportunityType()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'opportunity_type_id');
  }

  public function clientStatus()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'client_status_id');
  }

  public function opportunityStatus()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'opportunity_status_id');
  }

  public function actions()
  {
    return $this->hasMany(OpportunityAction::class, 'opportunity_id');
  }
}
