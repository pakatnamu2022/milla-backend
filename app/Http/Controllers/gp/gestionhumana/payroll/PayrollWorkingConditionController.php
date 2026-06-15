<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\ImportWorkingConditionRequest;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollWorkingConditionRequest;
use App\Http\Services\gp\gestionhumana\payroll\WorkingConditionService;
use Exception;

class PayrollWorkingConditionController extends Controller
{
  protected WorkingConditionService $service;

  public function __construct(WorkingConditionService $service)
  {
    $this->service = $service;
  }

  public function index(IndexPayrollWorkingConditionRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Importa condiciones de trabajo desde un archivo Excel.
   *
   * Formato esperado:
   * - Fila 1: título (ignorar)
   * - Fila 2: cabeceras (B = DNI, C = C.T)
   * - Fila 3+: datos (DNI en columna B, monto en columna C)
   */
  public function import(ImportWorkingConditionRequest $request)
  {
    if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
      return $this->error('Archivo no válido. Asegúrate de enviar un archivo Excel con el campo "file".');
    }

    try {
      $result = $this->service->importFromExcel(
        $request->file('file'),
        $request->input('period_id')
      );

      if ($result['success']) {
        return $this->success($result, $result['message'] ?? 'Importación completada');
      }

      return $this->success($result);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
