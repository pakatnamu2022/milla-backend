<?php

namespace App\Models\ap\comercial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessPartnersEstablishment extends Model
{
  use SoftDeletes;

  protected $table = 'business_partners_establishment';

  protected $fillable = [
    'code',
    'type',
    'activity_economic',
    'address',
    'full_address',
    'ubigeo',
    'business_partner_id',
  ];

  public function businessPartner(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'business_partner_id');
  }
}
