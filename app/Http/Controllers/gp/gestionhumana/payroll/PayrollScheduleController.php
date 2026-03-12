<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollScheduleRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StorePayrollScheduleRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StoreBulkPayrollScheduleRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollScheduleService;
use Exception;
use Illuminate\Http\Request;

class PayrollScheduleController extends Controller
{
  protected PayrollScheduleService $service;

  public function __construct(PayrollScheduleService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of schedules
   */
  public function index(IndexPayrollScheduleRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Store a newly created schedule
   */
  public function store(StorePayrollScheduleRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Store multiple schedules at once
   */
  public function storeBulk(StoreBulkPayrollScheduleRequest $request)
  {
    try {
      $result = $this->service->storeBulk($request->validated());
      return $this->success([
        'data' => $result,
        'message' => "{$result['created_count']} schedules created/updated successfully"
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Display the specified schedule
   */
  public function show(int $id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Update the specified schedule
   */
  public function update(Request $request, int $id)
  {
    try {
      $data = $request->only(['code', 'notes', 'status']);
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Remove the specified schedule
   */
  public function destroy(int $id)
  {
    try {
      return $this->service->destroy($id);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get summary of hours by period
   */
  public function summary(int $periodId)
  {
    try {
      return $this->success($this->service->getSummaryByPeriod($periodId));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Generate payroll calculations for a period
   * Creates PayrollCalculation records from attendance schedules
   * Optional query param: quincena=1 (primera quincena) | quincena=2 (segunda quincena)
   */
  public function generateCalculations(Request $request, int $periodId)
  {
    try {
      $quincena = $request->query('quincena') !== null ? (int)$request->query('quincena') : null;
      $result = $this->service->generatePayrollCalculations($periodId, $quincena);
      return $this->success([
        'data' => $result,
        'message' => "Successfully generated {$result['calculations_created']} payroll calculations"
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Recalculate payroll calculations for a period
   * Deletes and regenerates calculations based on current schedules
   * Optional query param: quincena=1 (primera quincena) | quincena=2 (segunda quincena)
   */
  public function recalculateCalculations(Request $request, int $periodId)
  {
    try {
      $quincena = $request->query('quincena') !== null ? (int)$request->query('quincena') : null;
      $result = $this->service->recalculatePayrollCalculations($periodId, $quincena);
      return $this->success([
        'data' => $result,
        'message' => "Successfully recalculated {$result['calculations_created']} payroll calculations"
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get daily attendances for all workers in a period
   * Returns data for frontend to map each worker's attendance codes
   * Optional query param: quincena=1 (primera quincena) | quincena=2 (segunda quincena)
   */
  public function getAttendances(Request $request, int $periodId)
  {
    try {
      $quincena = $request->query('quincena') !== null ? (int)$request->query('quincena') : null;
      return $this->success($this->service->getAttendancesByPeriod($periodId, $quincena));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
