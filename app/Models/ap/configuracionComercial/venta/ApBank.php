<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\ap\configuracionComercial\vehiculo\ApCommercialMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\gp\gestionsistema\CompanyBranch;
use App\Models\gp\gestionsistema\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApBank extends Model
{
  use softDeletes;

  protected $table = 'ap_bank';

  protected $fillable = [
    'code',
    'account_number',
    'cci',
    'bank_id',
    'currency_id',
    'company_branch_id',
    'status',
    'sede_id', //temporal
  ];

  const filters = [
    'search' => ['code', 'account_number', 'cci'],
    'bank_id' => '=',
    'currency_id' => '=',
    'company_branch_id' => '=',
    'status' => '=',
    'sede_id' => '=', //temporal
  ];

  const sorts = [
    'code',
    'account_number',
    'cci',
    'bank_id',
    'currency_id',
    'company_branch_id',
    'status',
    'sede_id', //temporal
  ];

  public function bank()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'bank_id');
  }

  public function currency()
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
  }

  public function companyBranch()
  {
    return $this->belongsTo(CompanyBranch::class, 'company_branch_id');
  }

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }
}
