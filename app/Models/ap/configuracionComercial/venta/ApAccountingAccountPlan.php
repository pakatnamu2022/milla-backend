<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\ap\ApMasters;
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
    'accounting_type_id',
    'status',
  ];

  const filters = [
    'search' => ['account', 'description', 'code_dynamics'],
    'code_dynamics' => '=',
    'accounting_type_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'account',
    'code_dynamics',
    'description',
    'accounting_type_id',
    'status',
  ];

  public function setAccountAttribute($value)
  {
    $this->attributes['account'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function typeAccount()
  {
    return $this->belongsTo(ApMasters::class, 'accounting_type_id');
  }
}
