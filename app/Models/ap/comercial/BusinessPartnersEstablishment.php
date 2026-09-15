<?php

namespace App\Models\ap\comercial;

use App\Models\BaseModel;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BusinessPartnersEstablishment extends BaseModel
{
  use SoftDeletes;

  protected $table = 'business_partners_establishment';

  protected $fillable = [
    'code',
    'description',
    'type',
    'activity_economic',
    'address',
    'full_address',
    'ubigeo',
    'status',
    'business_partner_id',
    'sede_id',
  ];

  const array filters = [
    'search'              => ['code', 'description', 'address', 'full_address'],
    'status'              => '=',
    'business_partner_id' => '=',
    'sede_id'             => '=',
  ];

  const array sorts = [
    'id',
    'code',
    'description',
    'type',
    'activity_economic',
    'address',
    'full_address',
    'ubigeo',
    'status',
    'business_partner_id',
  ];

  public function setCodeAttribute($value): void
  {
    $this->attributes['code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value): void
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function setTypeAttribute($value): void
  {
    $this->attributes['type'] = Str::upper(Str::ascii($value));
  }

  public function setActivityEconomicAttribute($value): void
  {
    $this->attributes['activity_economic'] = Str::upper(Str::ascii($value));
  }

  public function setAddressAttribute($value): void
  {
    $this->attributes['address'] = Str::upper(Str::ascii($value));
  }

  public function setFullAddressAttribute($value): void
  {
    $this->attributes['full_address'] = Str::upper(Str::ascii($value));
  }

  public function businessPartner(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'business_partner_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }
}
