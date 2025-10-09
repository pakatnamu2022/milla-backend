<?php

namespace App\Models\ap\postventa;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovedAccessories extends Model
{
  use SoftDeletes;

  protected $table = 'approved_accessories';

  protected $fillable = [
    'code',
    'type',
    'description',
    'exchange_rate',
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
    'exchange_rate',
    'price',
  ];

  public function typeCurrency()
  {
    return $this->belongsTo(TypeCurrency::class, 'type_currency_id');
  }

  public function bodyType()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'body_type_id');
  }
}
