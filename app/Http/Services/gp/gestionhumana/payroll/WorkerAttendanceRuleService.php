<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\AttendanceRuleResource;
use App\Models\gp\gestionhumana\payroll\AttendanceRule;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Support\Facades\DB;

class WorkerAttendanceRuleService
{
  /**
   * Retorna las reglas de asistencia permitidas para un worker.
   * Si no tiene ninguna asignada, retorna todas las reglas disponibles
   * con has_restriction = false (sin candado).
   */
  public function getByWorker(int $workerId): array
  {
    $worker = $this->findWorker($workerId);

    $allowedRules = $worker->allowedAttendanceRules()->orderBy('code')->get();
    $hasRestriction = $allowedRules->isNotEmpty();

    $rules = $hasRestriction
      ? $allowedRules
      : AttendanceRule::orderBy('code')->get();

    return [
      'has_restriction' => $hasRestriction,
      'rules' => AttendanceRuleResource::collection($rules),
    ];
  }

  /**
   * Agrega uno o varios códigos de asistencia a un worker.
   */
  public function addCodes(int $workerId, array $codes): array
  {
    $worker = $this->findWorker($workerId);
    $this->validateCodes($codes);

    try {
      DB::beginTransaction();
      $worker->allowedAttendanceRules()->syncWithoutDetaching($codes);
      DB::commit();
      return $this->getByWorker($workerId);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Reemplaza todos los códigos permitidos del worker (sync completo).
   * Pasar array vacío elimina todas las restricciones (sin candado).
   */
  public function syncCodes(int $workerId, array $codes): array
  {
    $worker = $this->findWorker($workerId);

    if (!empty($codes)) {
      $this->validateCodes($codes);
    }

    try {
      DB::beginTransaction();
      $worker->allowedAttendanceRules()->sync($codes);
      DB::commit();
      return $this->getByWorker($workerId);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Elimina un código de asistencia de un worker.
   */
  public function removeCode(int $workerId, string $code): array
  {
    $worker = $this->findWorker($workerId);

    try {
      DB::beginTransaction();
      $worker->allowedAttendanceRules()->detach($code);
      DB::commit();
      return $this->getByWorker($workerId);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Valida que los códigos existan en attendance_rules.
   */
  private function validateCodes(array $codes): void
  {
    $existing = AttendanceRule::whereIn('code', $codes)->pluck('code')->toArray();
    $invalid = array_diff($codes, $existing);

    if (!empty($invalid)) {
      throw new Exception('Códigos de asistencia no encontrados: ' . implode(', ', $invalid));
    }
  }

  private function findWorker(int $workerId): Worker
  {
    $worker = Worker::find($workerId);
    if (!$worker) {
      throw new Exception('Worker no encontrado');
    }
    return $worker;
  }
}
