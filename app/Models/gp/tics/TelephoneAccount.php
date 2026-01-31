<?php

namespace App\Models\gp\tics;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Database\Eloquent\SoftDeletes;

class TelephoneAccount extends BaseModel
{
  use SoftDeletes;

  protected $table = 'telephone_account';

  protected $fillable = [
    'company_id',
    'account_number',
  ];

  const filters = [
    'id' => '=',
    'search' => ['account_number'],
    'company_id' => '=',
    'account_number' => 'like',
  ];

  const sorts = [
    'id' => 'asc',
    'account_number' => 'asc',
  ];

  /**
   * Relación con la empresa
   */
  public function company()
  {
    return $this->belongsTo(Company::class, 'company_id');
  }

  /**
   * Relación con las líneas telefónicas de esta cuenta
   */
  public function phoneLines()
  {
    return $this->hasMany(PhoneLine::class, 'telephone_account_id');
  }
}
