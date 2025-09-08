<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\ap\configuracionComercial\vehiculo\ApCommercialMasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApAccountingAccountPlan extends Model
{
  use softDeletes;

  protected $table = 'ap_accounting_account_plan';

  protected $fillable = [
    'account',
    'description',
    'accounting_type_id',
    'status',
  ];

  const filters = [
    'search' => ['account', 'description'],
    'accounting_type_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'account',
    'description',
    'accounting_type_id',
    'status',
  ];

  public function typeAccount()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'accounting_type_id');
  }
}
