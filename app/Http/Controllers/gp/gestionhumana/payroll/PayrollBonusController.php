<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\ImportPayrollBonusRequest;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollBonusRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StorePayrollBonusRequest;
use App\Http\Requests\gp\gestionhumana\payroll\UpdatePayrollBonusRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollBonusService;
use Exception;
use Illuminate\Http\Request;

class PayrollBonusController extends Controller
{
    protected PayrollBonusService $service;

    public function __construct(PayrollBonusService $service)
    {
        $this->service = $service;
    }

    public function index(IndexPayrollBonusRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function store(StorePayrollBonusRequest $request)
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

    public function update(UpdatePayrollBonusRequest $request, int $id)
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
     * Descarga la plantilla Excel para cargar bonificaciones. Query params: company_id.
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
     * Importa bonificaciones desde un archivo Excel.
     *
     * Formato esperado:
     * - Fila 1: título (ignorar)
     * - Fila 2: cabeceras (A = DNI, B = MONTO)
     * - Fila 3+: datos
     */
    public function import(ImportPayrollBonusRequest $request)
    {
        if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
            return $this->error('Archivo no válido. Asegúrate de enviar un archivo Excel con el campo "file".');
        }

        try {
            $result = $this->service->importFromExcel(
                $request->file('file'),
                (int) $request->input('period_id'),
                (int) $request->input('type_id'),
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