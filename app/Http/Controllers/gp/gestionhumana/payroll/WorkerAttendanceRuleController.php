<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Services\gp\gestionhumana\payroll\WorkerAttendanceRuleService;
use Exception;
use Illuminate\Http\Request;

class WorkerAttendanceRuleController extends Controller
{
  protected WorkerAttendanceRuleService $service;

  public function __construct(WorkerAttendanceRuleService $service)
  {
    $this->service = $service;
  }

  /**
   * Retorna las reglas permitidas para un worker.
   * Si no tiene restricciones, retorna todas con has_restriction = false.
   * GET /workers/{workerId}/attendance-rules
   */
  public function index(int $workerId)
  {
    try {
      return $this->success($this->service->getByWorker($workerId));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Agrega códigos de asistencia permitidos al worker.
   * POST /workers/{workerId}/attendance-rules
   * Body: { "codes": ["HN", "HED"] }
   */
  public function store(Request $request, int $workerId)
  {
    $request->validate([
      'codes' => ['required', 'array', 'min:1'],
      'codes.*' => ['required', 'string', 'max:10'],
    ]);

    try {
      return $this->success($this->service->addCodes($workerId, $request->input('codes')));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Reemplaza todos los códigos permitidos del worker.
   * Enviar codes = [] elimina el candado (sin restricción).
   * POST /workers/{workerId}/attendance-rules/sync
   * Body: { "codes": ["HN", "F"] }
   */
  public function sync(Request $request, int $workerId)
  {
    $request->validate([
      'codes' => ['required', 'array'],
      'codes.*' => ['string', 'max:10'],
    ]);

    try {
      return $this->success($this->service->syncCodes($workerId, $request->input('codes')));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Elimina un código de asistencia del worker.
   * DELETE /workers/{workerId}/attendance-rules/{code}
   */
  public function destroy(int $workerId, string $code)
  {
    try {
      return $this->success($this->service->removeCode($workerId, $code));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
