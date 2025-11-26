<?php

namespace App\Models\ap;

use App\Models\ap\comercial\Opportunity;
use App\Models\gp\gestionsistema\Person;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApCommercialMasters extends Model
{
  use SoftDeletes;

  protected $table = 'ap_commercial_masters';

  protected $fillable = [
    'id',
    'code',
    'description',
    'type',
    'status',
  ];

  const filters = [
    'search' => ['code', 'description', 'type'],
    'type' => '=',
    'status' => '=',
    'open_opportunity_status' => 'accessor_bool',
  ];

  const sorts = [
    'code',
    'description',
    'type',
  ];

//  OPERATION TYPE
  const int TIPO_OPERACION_COMERCIAL = 794;
  const int TIPO_OPERACION_POSTVENTA = 804;

//  CURRENCY TYPE
  const int CURRENCY_TYPE_SOLES = 1;
  const int CURRENCY_TYPE_DOLLARS = 2;

  public function setCodeAttribute($value)
  {
    $this->attributes['code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function setTypeAttribute($value)
  {
    $this->attributes['type'] = Str::upper(Str::ascii($value));
  }

  public function scopeOfType($query, string $type)
  {
    return $query->where('type', strtoupper($type));
  }

  public function commercialManagers()
  {
    return $this->belongsToMany(Person::class, 'ap_commercial_manager_brand_group', 'brand_group_id', 'commercial_manager_id')
      ->withTimestamps();
  }

  public function sedes()
  {
    return $this->hasMany(Sede::class, 'shop_id');
  }

  public function getOpenOpportunityStatusAttribute()
  {
    return in_array($this->code, Opportunity::OPEN_STATUS_CODES);
  }
}
