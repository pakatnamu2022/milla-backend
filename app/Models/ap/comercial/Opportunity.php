<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApFamilies;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
    'has_purchase_request_quote' => 'accessor_bool',
    'opportunityType.description' => '=',
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

  const array OPEN_STATUS_CODES = [self::COLD, self::WARM, self::HOT];

  const int COLD_ID = 856;
  const int WARM_ID = 857;
  const int HOT_ID = 858;
  const int SOLD_ID = 859;
  const int CLOSED_ID = 860;
  const array OPPORTUNITY_STATUS_ID = [
    self::COLD_ID,
    self::WARM_ID,
    self::HOT_ID,
    self::SOLD_ID,
    self::CLOSED_ID,
  ];

  public function getIsClosedAttribute(): bool
  {
    return !in_array($this->opportunityStatus->code, self::OPEN_STATUS_CODES);
  }

  public function getHasPurchaseRequestQuoteAttribute(): bool
  {
    return $this->purchaseRequestsQuote()->exists();
  }

  public function purchaseRequestsQuote(): HasOne
  {
    return $this->hasOne(PurchaseRequestQuote::class, 'opportunity_id');
  }

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function client(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'client_id');
  }

  public function family(): BelongsTo
  {
    return $this->belongsTo(ApFamilies::class, 'family_id');
  }

  public function lead(): BelongsTo
  {
    return $this->belongsTo(PotentialBuyers::class, 'lead_id');
  }

  public function opportunityType(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'opportunity_type_id');
  }

  public function clientStatus(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'client_status_id');
  }

  public function opportunityStatus(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'opportunity_status_id');
  }

  public function actions(): HasMany
  {
    return $this->hasMany(OpportunityAction::class, 'opportunity_id');
  }
}
