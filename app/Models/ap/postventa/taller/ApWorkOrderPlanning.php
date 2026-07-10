<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\ApMasters;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\User;
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
    'group_number',
    'worker_id',
    'work_order_id',
    'canceled_note',
    'canceled_by',
    'canceled_at'
  ];

  protected $casts = [
    'estimated_hours' => 'decimal:2',
    'actual_hours' => 'decimal:2',
    'planned_start_datetime' => 'datetime',
    'planned_end_datetime' => 'datetime',
    'actual_start_datetime' => 'datetime',
    'actual_end_datetime' => 'datetime',
    'canceled_at' => 'datetime',
  ];

  // Constantes de horario laboral
  const WORK_START_TIME = '00:00';
  const LUNCH_START_TIME = '13:00';
  const LUNCH_END_TIME = '14:24';
  const WORK_END_TIME = '23:59';

  const filters = [
    'search' => ['description', 'workOrder.correlative'],
    'worker_id' => '=',
    'work_order_id' => '=',
    'planned_start_datetime' => 'date_between',
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

  public function workOrder(): BelongsTo
  {
    return $this->belongsTo(ApWorkOrder::class, 'work_order_id');
  }

  public function worker(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function canceledBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'canceled_by');
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
   *
   * NOTA: El técnico tiene libertad total para iniciar trabajos en cualquier momento,
   * sin restricciones de horario programado ni trabajos activos simultáneos.
   * Lo importante es registrar la duración real para auditoría.
   */
  public function startSession(?string $notes = null): ApWorkOrderPlanningSession
  {
    // VALIDACIÓN REMOVIDA: Anteriormente se validaba que no hubiera sesión activa
    // Razón: El técnico puede tener múltiples trabajos activos simultáneamente
    // if ($this->activeSession()) {
    //   throw new \Exception('Ya existe una sesión activa. Debe finalizarla antes de iniciar una nueva.');
    // }

    $workOrder = $this->workOrder;
    $workOrder->status_id = ApMasters::AT_WORK_WORK_ORDER_ID;
    $workOrder->save();

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
      $activeSession->endSession($pauseReason, 'paused');

      // Actualizar horas acumuladas
      // El status del planning se mantiene en 'in_progress' aunque la sesión esté pausada
      $this->actual_hours = $this->calculateTotalHoursWorked();
      $this->save();
    }
  }

  /**
   * Continúa un trabajo con sesiones pausadas
   *
   * NOTA: El técnico puede continuar cualquier trabajo en cualquier momento,
   * incluso si tiene otros trabajos activos simultáneamente.
   */
  public function continueSession(?string $notes = null): ApWorkOrderPlanningSession
  {
    // VALIDACIÓN REMOVIDA: Anteriormente se validaba que no hubiera sesión activa
    // Razón: El técnico puede tener múltiples trabajos activos simultáneamente
    // if ($this->activeSession()) {
    //   throw new \Exception('Ya existe una sesión activa. Debe finalizarla antes de continuar.');
    // }

    // Verificar que el trabajo esté en progreso (no 'planned' ni 'completed')
    if (!in_array($this->status, ['in_progress', 'planned'])) {
      throw new \Exception('No se puede continuar un trabajo que ya está completado o cancelado.');
    }

    $workOrder = $this->workOrder;
    $workOrder->status_id = ApMasters::AT_WORK_WORK_ORDER_ID;
    $workOrder->save();

    $session = new ApWorkOrderPlanningSession([
      'work_order_planning_id' => $this->id,
      'start_datetime' => now(),
      'status' => 'in_progress',
      'notes' => $notes,
    ]);
    $session->save();

    // Asegurar que el status esté en 'in_progress'
    if ($this->status !== 'in_progress') {
      $this->status = 'in_progress';
      $this->save();
    }

    return $session;
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

    // Verificar si todos los trabajos de la orden están completados
    $this->checkAndUpdateWorkOrderStatus();
  }

  /**
   * Verifica si todos los plannings de la orden de trabajo están completados
   * y actualiza el estado de la orden a FINISHED si es así
   */
  public function checkAndUpdateWorkOrderStatus(): void
  {
    $workOrder = $this->workOrder;

    if (!$workOrder) {
      return;
    }

    // Contar plannings pendientes o en progreso (sin contar los cancelados)
    $pendingPlannings = $workOrder->plannings()
      ->whereIn('status', ['planned', 'in_progress'])
      ->count();

    // Si no hay plannings pendientes, marcar la orden como terminada
    if ($pendingPlannings === 0) {
      $workOrder->status_id = ApMasters::END_WORK_WORK_ORDER_ID;
      $workOrder->save();
    }
  }
}
