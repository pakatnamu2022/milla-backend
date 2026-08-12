<?php

namespace App\Models\ap\marketing;

use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MktBudgetFunding extends Model
{
  protected $table = 'ap_mkt_budget_fundings';

  const SOURCE_AP    = 'AP';
  const SOURCE_BRAND = 'brand';

  protected $fillable = [
    'budget_id',
    'source',
    'currency_id',
    'amount',
    'notes',
  ];

  protected $casts = [
    'amount' => 'decimal:2',
  ];

  const filters = [
    'budget_id'   => '=',
    'source'      => '=',
    'currency_id' => '=',
  ];

  const sorts = [
    'source',
    'amount',
    'created_at',
  ];

  public function budget(): BelongsTo
  {
    return $this->belongsTo(MktBudget::class, 'budget_id');
  }

  public function currency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
  }
}
