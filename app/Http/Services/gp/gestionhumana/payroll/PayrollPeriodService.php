<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollPeriodResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollPeriodService extends BaseService implements BaseServiceInterface
{
  /**
   * Get all periods with filters and pagination
   */
  public function list(Request $request)
  {
    $query = PayrollPeriod::with(['company'])->orderBy('year', 'desc')->orderBy('month', 'desc');

    return $this->getFilteredResults(
      $query,
      $request,
      PayrollPeriod::filters,
      PayrollPeriod::sorts,
      PayrollPeriodResource::class,
    );
  }

  /**
   * Find a period by ID
   */
  public function find($id)
  {
    $period = PayrollPeriod::with(['company'])->find($id);
    if (!$period) {
      throw new Exception('Period not found');
    }
    return $period;
  }

  /**
   * Show a period by ID
   */
  public function show($id)
  {
    return new PayrollPeriodResource($this->find($id));
  }

  /**
   * Create a new period
   */
  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();

      $year = $data['year'];
      $month = $data['month'];
      $companyId = $data['company_id'] ?? null;

      // Check if period already exists
      $existing = PayrollPeriod::where('year', $year)
        ->where('month', $month)
        ->where('company_id', $companyId)
        ->first();

      if ($existing) {
        throw new Exception('Period already exists for this year, month and company');
      }

      // Calculate start and end dates
      $startDate = Carbon::create($year, $month, 1);
      $endDate = $startDate->copy()->endOfMonth();

      $period = PayrollPeriod::create([
        'code' => PayrollPeriod::generateCode($year, $month),
        'name' => PayrollPeriod::generateName($year, $month),
        'year' => $year,
        'month' => $month,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'payment_date' => $data['payment_date'] ?? null,
        'biweekly_date' => $data['biweekly_date'] ?? null,
        'status' => PayrollPeriod::STATUS_OPEN,
        'company_id' => $companyId,
      ]);

      DB::commit();
      return new PayrollPeriodResource($period->load('company'));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Update a period
   */
  public function update(mixed $data)
  {
    try {
      DB::beginTransaction();

      $period = $this->find($data['id']);

      if ($period->status !== PayrollPeriod::STATUS_OPEN) {
        throw new Exception('No se puede editar el período: solo se permite editar cuando está en estado ABIERTO. Estado actual: ' . $period->status);
      }

      $period->update([
        'payment_date' => $data['payment_date'] ?? $period->payment_date,
        'biweekly_date' => array_key_exists('biweekly_date', $data) ? $data['biweekly_date'] : $period->biweekly_date,
        'status' => $data['status'] ?? $period->status,
      ]);

      DB::commit();
      return new PayrollPeriodResource($period->fresh()->load('company'));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Delete a period
   */
  public function destroy($id)
  {
    try {
      DB::beginTransaction();

      $period = $this->find($id);

      if ($period->status !== PayrollPeriod::STATUS_OPEN) {
        throw new Exception('No se puede eliminar el período: solo se permite eliminar cuando está en estado ABIERTO. Estado actual: ' . $period->status);
      }

      // Check if period has schedules or calculations
      if ($period->schedules()->exists() || $period->calculations()->exists()) {
        throw new Exception('No se puede eliminar el período: tiene horarios o cálculos asociados');
      }

      $period->delete();

      DB::commit();
      return response()->json(['message' => 'Período eliminado exitosamente']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get current period
   */
  public function getCurrentPeriod(?int $companyId = null)
  {
    $period = PayrollPeriod::getCurrentPeriod($companyId);

    if (!$period) {
      throw new Exception('No se ha encontrado un período abierto para la empresa especificada');
    }

    return new PayrollPeriodResource($period->load('company'));
  }

  /**
   * Reset a period back to OPEN status.
   * Deletes all associated calculations and details so the period can be edited again.
   */
  public function resetPeriod(int $id)
  {
    try {
      DB::beginTransaction();

      $period = $this->find($id);

      if ($period->status === PayrollPeriod::STATUS_OPEN) {
        throw new Exception('El período ya está en estado ABIERTO.');
      }

      if ($period->status === PayrollPeriod::STATUS_CLOSED) {
        throw new Exception('No se puede reabrir un período CERRADO.');
      }

      // Load existing calculations (including soft-deleted)
      $calculations = \App\Models\gp\gestionhumana\payroll\PayrollCalculation::withTrashed()
        ->where('period_id', $id)
        ->get();

      if ($calculations->isNotEmpty()) {
        \App\Models\gp\gestionhumana\payroll\PayrollCalculationDetail::whereIn('calculation_id', $calculations->pluck('id'))
          ->forceDelete();

        \App\Models\gp\gestionhumana\payroll\PayrollCalculation::withTrashed()
          ->where('period_id', $id)
          ->forceDelete();
      }

      $period->update(['status' => PayrollPeriod::STATUS_OPEN]);

      DB::commit();
      return new PayrollPeriodResource($period->fresh()->load('company'));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Close a period
   */
  public function closePeriod(int $id)
  {
    try {
      DB::beginTransaction();

      $period = $this->find($id);

      if ($period->status !== PayrollPeriod::STATUS_APPROVED) {
        throw new Exception('No se puede cerrar periodo: debe estar en estado APROBADO');
      }

      $period->update(['status' => PayrollPeriod::STATUS_CLOSED]);

      DB::commit();
      return new PayrollPeriodResource($period->fresh()->load('company'));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Set period to processing status
   */
  public function setProcessing(int $id)
  {
    try {
      DB::beginTransaction();

      $period = $this->find($id);

      if (!in_array($period->status, [PayrollPeriod::STATUS_OPEN, PayrollPeriod::STATUS_CALCULATED])) {
        throw new Exception('No se puede establecer el período de procesamiento: estado actual no válido');
      }

      $period->update(['status' => PayrollPeriod::STATUS_PROCESSING]);

      DB::commit();
      return new PayrollPeriodResource($period->fresh()->load('company'));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
