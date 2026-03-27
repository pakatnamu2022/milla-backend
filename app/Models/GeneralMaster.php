<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GeneralMaster extends Model
{
  use SoftDeletes;

  protected $table = 'general_masters';

  protected $fillable = [
    'id',
    'code',
    'description',
    'type',
    'value',
    'status',
  ];

  const filters = [
    'search' => ['code', 'description', 'type'],
    'type' => 'in_or_equal',
    'status' => '=',
    'code' => '=',
  ];

  const sorts = [
    'code',
    'description',
    'status',
    'type',
  ];

  const string PER_DIEM_MIN_DAYS = 'PER_DIEM_MIN_DAYS';
  const string DISCOUNT_PERCENTAGE_PV = 'DISCOUNT_PERCENTAGE_PV';
  const int MANAGER_DISCOUNT_PERCENTAGE_PV_ID = 2;
  const int BOSS_DISCOUNT_PERCENTAGE_PV_ID = 3;
  const int ADVISOR_DISCOUNT_PERCENTAGE_PV_ID = 4;
  const int SUNAT_DETRACTION_PERCENTAGE_ID = 5;

  //CONSTANTES DE PLANILLA
  const int DAYS_MONTH_ID = 6;
  const int WORKING_HOURS_ID = 7;
  const int NIGHT_SURCHARGE_ID = 8;
  const int MINIMUM_WAGE_ID = 13;

  //CONSTANTES POSTVENTA
  const int COST_PER_MAN_HOUR_VL_ID = 9;
  const int COST_PER_MAN_HOUR_VP_ID = 10;
  const int PROFIT_MARGIN_ID = 11;
  const int FREIGHT_COMMISSION_ID = 12;

  public function setCodeAttribute($value)
  {
    $this->attributes['code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function setTypeAttribute($value)
  {
    $this->attributes['type'] = Str::upper(Str::ascii($value));
  }

  public function scopeOfType($query, string $type)
  {
    return $query->where('type', strtoupper($type));
  }
}
