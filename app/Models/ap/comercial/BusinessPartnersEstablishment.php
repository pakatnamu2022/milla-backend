<?php

namespace App\Models\ap\comercial;

use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BusinessPartnersEstablishment extends Model
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

  const filters = [
    'search' => ['code', 'description', 'address', 'full_address'],
    'status' => '=',
    'business_partner_id' => '=',
    'sede_id' => '=',
  ];

  const sorts = [
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

  public function setActivityEconomicAttribute($value)
  {
    $this->attributes['activity_economic'] = Str::upper(Str::ascii($value));
  }

  public function setAddressAttribute($value)
  {
    $this->attributes['address'] = Str::upper(Str::ascii($value));
  }

  public function setFullAddressAttribute($value)
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
