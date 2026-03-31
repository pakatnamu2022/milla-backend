<?php

namespace App\Models\ap\configuracionComercial\venta;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApAccountingAccountPlan extends Model
{
  use softDeletes;

  protected $table = 'ap_accounting_account_plan';

  protected $fillable = [
    'account',
    'code_dynamics',
    'description',
    'is_detraction',
    'type',
    'status',
  ];

  const filters = [
    'search' => ['account', 'description', 'code_dynamics'],
    'code_dynamics' => '=',
    'is_detraction' => '=',
    'status' => '=',
    'type' => '=',
  ];

  const sorts = [
    'account',
    'code_dynamics',
    'description',
    'is_detraction',
    'status',
  ];

  const int TYPE_SALE = 0;
  const int TYPE_CREDIT_NOTE = 1;
  const int TYPE_DEBIT_NOTE = 2;

  const LABOUR_ACCOUNT_ID = 24;
  const LABOUR_ACCOUNT_MATERIAL_ID = 25;

  public function setAccountAttribute($value)
  {
    $this->attributes['account'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }
}
