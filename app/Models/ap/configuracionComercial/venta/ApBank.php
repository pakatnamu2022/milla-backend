<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\BaseModel;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApBank extends BaseModel
{
  use softDeletes;

  protected $table = 'ap_bank';

  protected $fillable = [
    'code',
    'description',
    'account_number',
    'cci',
    'bank_id',
    'currency_id',
    'status',
    'sede_id',
  ];

  const filters = [
    'search' => ['code', 'account_number', 'cci', 'bank.description'],
    'bank_id' => '=',
    'currency_id' => '=',
    'status' => '=',
    'sede_id' => '=',
  ];

  const sorts = [
    'code',
    'account_number',
    'cci',
    'bank_id',
    'currency_id',
    'status',
    'sede_id',
  ];

  public function bank(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'bank_id');
  }

  public function currency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }
}
