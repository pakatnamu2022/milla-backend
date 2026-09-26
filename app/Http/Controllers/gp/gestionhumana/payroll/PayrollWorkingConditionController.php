<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\ImportWorkingConditionRequest;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollWorkingConditionRequest;
use App\Http\Requests\gp\gestionhumana\payroll\UpdatePayrollWorkingConditionRequest;
use App\Http\Services\gp\gestionhumana\payroll\WorkingConditionService;
use Exception;
use Illuminate\Http\Request;

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

  public function update(UpdatePayrollWorkingConditionRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Descarga la plantilla Excel para cargar condiciones de trabajo, pre-llenada con los
   * trabajadores activos de la empresa. Query params: company_id.
   */
  public function downloadTemplate(Request $request)
  {
    $companyId = (int) $request->query('company_id');
    if (!$companyId) {
      return $this->error('company_id es requerido');
    }

    try {
      return $this->service->downloadTemplate($companyId);
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
