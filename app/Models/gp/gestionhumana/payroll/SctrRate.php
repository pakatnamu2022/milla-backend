<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class SctrRate extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_sctr_rates';

  protected $fillable = [
    'company_id',
    'health_rate',
    'pension_rate',
    'effective_from',
    'effective_to',
    'created_by',
  ];

  protected $casts = [
    'health_rate' => 'decimal:6',
    'pension_rate' => 'decimal:6',
    'effective_from' => 'date:Y-m-d',
    'effective_to' => 'date:Y-m-d',
  ];

  const filters = [
    'company_id' => '=',
  ];

  const sorts = [
    'effective_from',
    'created_at',
  ];

  public function scopeValidAt($query, $date)
  {
    $date = $date instanceof Carbon ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');

    return $query
      ->whereDate('effective_from', '<=', $date)
      ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date));
  }

  /**
   * Tasa SCTR vigente de una empresa en una fecha (fin del periodo de planilla).
   */
  public static function resolve(int $companyId, $date): ?self
  {
    return static::where('company_id', $companyId)
      ->validAt($date)
      ->orderByDesc('effective_from')
      ->first();
  }

  /**
   * Crea una tasa nueva y cierra la vigente de la empresa (effective_to = día antes del
   * effective_from de la nueva). La nueva queda con effective_to = null.
   */
  public static function createAndCloseCurrent(array $data): self
  {
    return DB::transaction(function () use ($data) {
      $from = Carbon::parse($data['effective_from']);

      $current = static::where('company_id', $data['company_id'])
        ->whereNull('effective_to')
        ->orderByDesc('effective_from')
        ->first();

      if ($current) {
        if ($from->lte(Carbon::parse($current->effective_from))) {
          throw new \Exception('La fecha de inicio debe ser posterior al inicio de la tasa vigente (' . $current->effective_from->format('d/m/Y') . ')');
        }
        $current->update(['effective_to' => $from->copy()->subDay()->format('Y-m-d')]);
      }

      $data['effective_to'] = null;

      return static::create($data);
    });
  }

  public function company(): BelongsTo
  {
    return $this->belongsTo(\App\Models\gp\gestionsistema\Company::class, 'company_id');
  }
}
