<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\ImportPayrollInsuranceRequest;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollInsuranceRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StorePayrollInsuranceRequest;
use App\Http\Requests\gp\gestionhumana\payroll\UpdatePayrollInsuranceRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollInsuranceService;
use Exception;

class PayrollInsuranceController extends Controller
{
  protected PayrollInsuranceService $service;

  public function __construct(PayrollInsuranceService $service)
  {
    $this->service = $service;
  }

  public function index(IndexPayrollInsuranceRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function store(StorePayrollInsuranceRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function show(int $id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function update(UpdatePayrollInsuranceRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function destroy(int $id)
  {
    try {
      return $this->service->destroy($id);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Importa seguros de planilla desde un archivo Excel.
   * Determina el formato según el business_partner_id:
   * - 13297: FESALUD SA
   * - 13298: ONCOSALUD S.A.C.
   */
  public function import(ImportPayrollInsuranceRequest $request)
  {
    if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
      return $this->error('Archivo no válido. Asegúrate de enviar un archivo Excel con el campo "file".');
    }

    try {
      $businessPartnerId = $request->input('business_partner_id');

      switch ($businessPartnerId) {
        case 13297: // FESALUD SA
          $result = $this->service->importFromExcelFesalud(
            $request->file('file'),
            $request->input('period_id'),
            $businessPartnerId
          );
          break;

        case 13298: // ONCOSALUD S.A.C.
          $result = $this->service->importFromExcelOncoplus(
            $request->file('file'),
            $request->input('period_id'),
            $businessPartnerId
          );
          break;

        default:
          return $this->error('El business_partner_id debe ser 13297 (FESALUD) o 13298 (ONCOSALUD).');
      }

      if ($result['success']) {
        return $this->success($result, $result['message'] ?? 'Importación completada');
      }

      return $this->success($result);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
