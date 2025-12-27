<?php

namespace App\Models\gp\gestionhumana\viaticos;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MobilityPayroll extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_mobility_payroll';

  protected $fillable = [
    'worker_id',
    'num_doc',
    'company_name',
    'address',
    'serie',
    'correlative',
    'period',
    'sede_id',
  ];

  /**
   * Get the worker that owns the mobility payroll
   */
  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  /**
   * Get the sede that owns the mobility payroll
   */
  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  /**
   * Get all expenses for this mobility payroll
   */
  public function expenses(): HasMany
  {
    return $this->hasMany(PerDiemExpense::class, 'mobility_payroll_id');
  }

  /**
   * Generate next correlative number for a given serie, period and sede_id
   */
  public static function getNextCorrelative(string $serie, string $period, ?int $sedeId = null): string
  {
    $query = self::where('serie', $serie)
      ->where('period', $period);

    // Filter by sede_id if provided
    if (!is_null($sedeId)) {
      $query->where('sede_id', $sedeId);
    }

    $lastPayroll = $query->orderBy('correlative', 'desc')
      ->first();

    if (!$lastPayroll) {
      return '00001';
    }

    $nextNumber = intval($lastPayroll->correlative) + 1;
    return str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
  }

  /**
   * Get the full document number (serie-correlative)
   */
  public function getDocumentNumberAttribute(): string
  {
    return "{$this->serie}-{$this->correlative}";
  }
}