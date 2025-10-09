<?php

namespace App\Models\gp\maestroGeneral;

use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExchangeRate extends BaseModel
{
  use SoftDeletes;

  protected $table = 'exchange_rate';

  protected $fillable = [
    'from_currency_id',
    'to_currency_id',
    'type',
    'date',
    'rate',
  ];

  const filters = [
    'from_currency_id' => '=',
    'to_currency_id' => '=',
    'type' => '=',
    'date' => '=',
    'rate' => '=',
  ];

  const sorts = [
    'from_currency_id',
    'to_currency_id',
    'type',
    'date',
    'rate',
  ];

  const TYPE_VENTA = 'VENTA';
  const TYPE_NEGOCIADOR = 'NEGOCIADOR';

  public function fromCurrency()
  {
    return $this->belongsTo(TypeCurrency::class, 'from_currency_id');
  }

  public function toCurrency()
  {
    return $this->belongsTo(TypeCurrency::class, 'to_currency_id');
  }
}
