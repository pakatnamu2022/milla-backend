<?php

namespace App\Models\ap\postventa\taller;

use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrderLabour extends Model
{
  use SoftDeletes;

  protected $table = 'work_order_labour';

  protected $fillable = [
    'group_number',
    'description',
    'time_spent',
    'hourly_rate',
    'discount_percentage',
    'total_cost',
    'worker_id',
    'work_order_id'
  ];

  const filters = [
    'search' => ['description', 'worker.worker_id'],
    'worker_id' => '=',
    'work_order_id' => '=',
  ];

  const sorts = [
    'id',
    'description',
    'time_spent',
    'hourly_rate',
    'total_cost',
    'worker_id',
    'created_at',
  ];

  protected $casts = [
    'hourly_rate' => 'decimal:2',
    'discount_percentage' => 'decimal:2',
    'total_cost' => 'decimal:2',
  ];

  public function setDescriptionAttribute($value): void
  {
    $this->attributes['description'] = strtoupper($value);
  }

  /**
   * Mutador para convertir horas decimales a formato TIME
   * Acepta: 2.5 (horas decimales), "02:30", "02:30:00"
   */
  public function setTimeSpentAttribute($value): void
  {
    if (is_numeric($value)) {
      // Convertir decimal a HH:MM:SS
      $hours = floor($value);
      $minutes = round(($value - $hours) * 60);

      // Ajustar si los minutos son 60
      if ($minutes >= 60) {
        $hours += 1;
        $minutes = 0;
      }

      $this->attributes['time_spent'] = sprintf('%02d:%02d:00', $hours, $minutes);
    } else {
      // Si ya viene en formato HH:MM o HH:MM:SS, usarlo directamente
      $this->attributes['time_spent'] = $value;
    }
  }

  /**
   * Accessor para obtener el tiempo en formato decimal (horas)
   */
  public function getTimeSpentDecimalAttribute(): float
  {
    if (!$this->time_spent) {
      return 0;
    }

    $parts = explode(':', $this->time_spent);
    $hours = intval($parts[0]);
    $minutes = isset($parts[1]) ? intval($parts[1]) : 0;

    return $hours + ($minutes / 60);
  }

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function workOrder(): BelongsTo
  {
    return $this->belongsTo(ApWorkOrder::class, 'work_order_id');
  }
}
