<?php

namespace App\Models\ap\marketing;

use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MktActivityLocation extends Model
{
  protected $table = 'mkt_activity_locations';

  protected $fillable = [
    'activity_id',
    'sede_id',
    'location_name',
    'currency_id',
    'amount',
    'notes',
  ];

  protected $casts = [
    'amount' => 'decimal:2',
  ];

  const filters = [
    'activity_id' => '=',
    'sede_id'     => '=',
    'currency_id' => '=',
  ];

  const sorts = [
    'location_name',
    'amount',
    'created_at',
  ];

  public function activity(): BelongsTo
  {
    return $this->belongsTo(MktActivity::class, 'activity_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(\App\Models\gp\maestroGeneral\Sede::class, 'sede_id');
  }

  public function currency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
  }
}
