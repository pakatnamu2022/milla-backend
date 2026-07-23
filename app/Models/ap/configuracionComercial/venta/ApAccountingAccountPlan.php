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
    'detraction_percentage',
    'type',
    'status',
    'enable_commercial',
    'enable_after_sales',
  ];

  const filters = [
    'search' => ['account', 'description', 'code_dynamics'],
    'code_dynamics' => '=',
    'is_detraction' => '=',
    'status' => '=',
    'type' => '=',
    'enable_commercial' => '=',
    'enable_after_sales' => '=',
  ];

  const sorts = [
    'account',
    'code_dynamics',
    'description',
    'is_detraction',
    'status',
  ];

  const array DETRACTION_PERCENTAGES = [10, 12];

  protected $casts = [
    'is_detraction' => 'boolean',
    'detraction_percentage' => 'integer',
    'enable_commercial' => 'boolean',
    'enable_after_sales' => 'boolean',
  ];

  const int TYPE_SALE = 0;
  const int TYPE_CREDIT_NOTE = 1;
  const int TYPE_DEBIT_NOTE = 2;

  const LABOUR_ACCOUNT_ID = 24;
  const LABOUR_ACCOUNT_MATERIAL_ID = 25;
  const AFTER_SALES_MAINTENANCE_SERVICE_ID = 29;
  const ADVANCE_PAYMENTS_ACCOUNT_ID = 2;
  const ELECTRONIC_DOCUMENTS_REPORT_ACCOUNT_ID = 8;

  public function setAccountAttribute($value)
  {
    $this->attributes['account'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }
}
