<?php

namespace App\Models\ap\postventa\taller;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApWorkOrderPlanningSession extends Model
{
  use SoftDeletes;

  protected $table = 'work_order_planning_sessions';

  protected $fillable = [
    'work_order_planning_id',
    'start_datetime',
    'end_datetime',
    'hours_worked',
    'status',
    'pause_reason',
    'notes',
  ];

  protected $casts = [
    'start_datetime' => 'datetime',
    'end_datetime' => 'datetime',
    'hours_worked' => 'decimal:2',
  ];

  const filters = [
    'work_order_planning_id' => '=',
    'status' => '=',
    'start_datetime' => 'between',
    'end_datetime' => 'between',
  ];

  const sorts = [
    'id',
    'start_datetime',
    'end_datetime',
    'hours_worked',
    'created_at',
  ];

  public function setPauseReasonAttribute($value)
  {
    $this->attributes['pause_reason'] = strtoupper($value);
  }

  /**
   * Relación con la planificación de la orden de trabajo
   */
  public function workOrderPlanning(): BelongsTo
  {
    return $this->belongsTo(ApWorkOrderPlanning::class, 'work_order_planning_id');
  }

  /**
   * Calcula las horas trabajadas basándose en start y end datetime
   */
  public function calculateHoursWorked(): ?float
  {
    if ($this->start_datetime && $this->end_datetime) {
      // Asegurar que sean objetos Carbon
      $start = Carbon::parse($this->start_datetime);
      $end = Carbon::parse($this->end_datetime);

      // Calcular diferencia en minutos (más preciso)
      $diffInMinutes = $start->diffInMinutes($end);

      // Convertir a horas con 2 decimales
      return round($diffInMinutes / 60, 2);
    }
    return null;
  }

  /**
   * Finaliza la sesión actual
   */
  public function endSession(?string $pauseReason = null, string $status = 'completed'): void
  {
    $this->end_datetime = now();
    $this->hours_worked = $this->calculateHoursWorked();
    $this->status = $status;
    $this->pause_reason = $pauseReason;
    $this->save();
  }

  /**
   * Verifica si la sesión está activa
   */
  public function isActive(): bool
  {
    return $this->status === 'in_progress' && is_null($this->end_datetime);
  }
}

