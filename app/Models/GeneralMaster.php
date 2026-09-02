<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class GeneralMaster extends BaseModel
{
  use SoftDeletes;

  protected $table = 'general_masters';

  protected $fillable = [
    'id',
    'code',
    'description',
    'type',
    'value',
    'effective_from',
    'effective_to',
    'status',
  ];

  protected $casts = [
    'effective_from' => 'date:Y-m-d',
    'effective_to' => 'date:Y-m-d',
  ];

  const filters = [
    'search' => ['code', 'description', 'type'],
    'type'   => 'in_or_equal',
    'status' => '=',
    'code'   => '=',
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
  const int BOSS_DISCOUNT_PERCENTAGE_PVT_ID = 3;
  const int ADVISOR_DISCOUNT_PERCENTAGE_PV_ID = 4;
  const int BOSS_DISCOUNT_PERCENTAGE_PVR_ID = 14;
  const int SUNAT_DETRACTION_PERCENTAGE_ID = 5;

  //CONSTANTES DE PLANILLA
  const int DAYS_MONTH_ID = 6;
  const int WORKING_HOURS_ID = 7;
  const int NIGHT_SURCHARGE_ID = 8;
  const int MINIMUM_WAGE_ID = 13;
  const int ESSALUD_RATE_ID = 19; // ES - EsSalud 9%
  const int LIFE_INSURANCE_RATE_ID = 20; // ES-VI - tasa Vida Ley 3.12%
  const int INSURABLE_MAX_REMUNERATION_ID = 22; // RMA - tope SCTR pensión
  const int SCTR_HEALTH_RATE_ID = 55;
  const int SCTR_PENSION_RATE_ID = 56;
  const int IGV_RATE_ID = 57;
  const int UIT_ID = 58;
  const int FAMILY_ALLOWANCE_ID = 59;
  const int INCOME_TAX_DEDUCTION_UIT_ID = 60;

  //CONSTANTES POSTVENTA
  const int COST_PER_MAN_HOUR_VL_ID = 9;
  const int COST_PER_MAN_HOUR_VP_ID = 10;
  const int PROFIT_MARGIN_ID = 11;
  const int FREIGHT_COMMISSION_ID = 12;
  const int COST_PER_MAN_HOUR_PDI_DERCO_ID = 16;

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

  /**
   * Filas cuya vigencia cubre $date (effective_from/effective_to null = sin límite en ese lado).
   */
  public function scopeValidAt($query, $date)
  {
    $date = $date instanceof Carbon ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');

    return $query
      ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date))
      ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date));
  }

  /**
   * Resuelve el valor de un parámetro (por código) vigente a una fecha dada, en vez de leer
   * "el valor actual" a ciegas. Uso obligatorio para parámetros de planilla que cambian por ley
   * (RMV/SALARIO_MINIMO, UIT, FAMILY_ALLOWANCE) al calcular periodos que no son el mes en curso,
   * para que recalcular un periodo antiguo no arrastre el valor vigente hoy.
   *
   * Si hay varias filas vigentes a esa fecha (no debería, pero por seguridad) se toma la de
   * effective_from más reciente.
   */
  public static function valueAt(string $code, $date, $default = null)
  {
    $row = static::where('code', strtoupper($code))
      ->where('status', 1)
      ->validAt($date)
      ->orderByDesc('effective_from')
      ->first();

    return $row?->value ?? $default;
  }
}
