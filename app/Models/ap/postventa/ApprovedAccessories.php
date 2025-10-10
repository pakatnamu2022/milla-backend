<?php

namespace App\Models\ap\postventa;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApprovedAccessories extends Model
{
  use SoftDeletes;

  protected $table = 'approved_accessories';

  protected $fillable = [
    'code',
    'type',
    'description',
    'price',
    'status',
    'type_currency_id',
    'body_type_id',
  ];

  const filters = [
    'search' => ['code', 'description', 'type'],
    'status' => '=',
    'type_currency_id' => '=',
    'body_type_id' => '=',
  ];

  const sorts = [
    'code',
    'type',
    'description',
    'price',
  ];

  public function setCodeAttribute($value): void
  {
    if ($value) {
      $this->attributes['code'] = Str::upper($value);
    }
  }

  public function setTypeAttribute($value): void
  {
    if ($value) {
      $this->attributes['type'] = Str::upper($value);
    }
  }
  
  public function setDescriptionAttribute($value): void
  {
    if ($value) {
      $this->attributes['description'] = Str::upper($value);
    }
  }

  public function typeCurrency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'type_currency_id');
  }

  public function bodyType(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'body_type_id');
  }
}
