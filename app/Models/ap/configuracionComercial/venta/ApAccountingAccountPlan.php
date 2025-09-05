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
    'cuenta',
    'descripcion',
    'tipo_cta_contable_id',
    'status',
  ];

  const filters = [
    'search' => ['cuenta', 'descripcion'],
    'tipo_cta_contable_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'cuenta',
    'descripcion',
    'tipo_cta_contable_id',
    'status',
  ];

  public function tipoCuenta()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'tipo_cta_contable_id');
  }
}
