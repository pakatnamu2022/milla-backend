<?php

namespace App\Models\ap\postventa\taller;

use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApWorkOrderPlanning extends Model
{
  use SoftDeletes;

  protected $table = 'work_order_planning';

  protected $fillable = [
    'description',
    'estimated_hours',
    'planned_start_datetime',
    'planned_end_datetime',
    'actual_hours',
    'actual_start_datetime',
    'actual_end_datetime',
    'status',
    'type',
    'worker_id',
    'work_order_id',
  ];

  protected $casts = [
    'estimated_hours' => 'decimal:2',
    'actual_hours' => 'decimal:2',
    'planned_start_datetime' => 'datetime',
    'planned_end_datetime' => 'datetime',
    'actual_start_datetime' => 'datetime',
    'actual_end_datetime' => 'datetime',
  ];

  const filters = [
    'search' => ['description', 'workOrder.correlative'],
    'worker_id' => '=',
    'work_order_id' => '=',
    'planned_start_datetime' => 'between',
    'planned_end_datetime' => 'between',
    'status' => '=',
    'workOrder.sede_id' => '=',
  ];

  const sorts = [
    'id',
    'planned_start_datetime',
    'planned_end_datetime',
    'created_at',
  ];

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = strtoupper($value);
  }

  /**
   * Relación con la orden de trabajo
   */
  public function workOrder(): BelongsTo
  {
    return $this->belongsTo(ApWorkOrder::class, 'work_order_id');
  }

  /**
   * Relación con el trabajador
   */
  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  /**
   * Relación con las sesiones de trabajo
   */
  public function sessions(): HasMany
  {
    return $this->hasMany(ApWorkOrderPlanningSession::class, 'work_order_planning_id');
  }

  /**
   * Obtiene la sesión activa actual (en progreso)
   */
  public function activeSession()
  {
    return $this->sessions()->where('status', 'in_progress')->whereNull('end_datetime')->first();
  }

  /**
   * Calcula el total de horas trabajadas sumando todas las sesiones completadas
   */
  public function calculateTotalHoursWorked(): float
  {
    return $this->sessions()
      ->whereNotNull('hours_worked')
      ->sum('hours_worked') ?? 0;
  }

  /**
   * Inicia una nueva sesión de trabajo
   */
  public function startSession(?string $notes = null): ApWorkOrderPlanningSession
  {
    // Verificar si ya hay una sesión activa
    if ($this->activeSession()) {
      throw new \Exception('Ya existe una sesión activa. Debe finalizarla antes de iniciar una nueva.');
    }

    $session = new ApWorkOrderPlanningSession([
      'work_order_planning_id' => $this->id,
      'start_datetime' => now(),
      'status' => 'in_progress',
      'notes' => $notes,
    ]);
    $session->save();

    // Actualizar la fecha de inicio real si es la primera sesión
    if (!$this->actual_start_datetime) {
      $this->actual_start_datetime = now();
      $this->status = 'in_progress';
      $this->save();
    }

    return $session;
  }

  /**
   * Pausa el trabajo actual
   */
  public function pauseWork(?string $pauseReason = null): void
  {
    $activeSession = $this->activeSession();
    if ($activeSession) {
      $activeSession->endSession($pauseReason);

      // Actualizar horas acumuladas
      $this->actual_hours = $this->calculateTotalHoursWorked();
      $this->save();
    }
  }

  /**
   * Completa el trabajo
   */
  public function completeWork(): void
  {
    // Finalizar sesión activa si existe
    $activeSession = $this->activeSession();
    if ($activeSession) {
      $activeSession->endSession();
    }

    // Actualizar horas y estado
    $this->actual_hours = $this->calculateTotalHoursWorked();
    $this->actual_end_datetime = now();
    $this->status = 'completed';
    $this->save();
  }
}
