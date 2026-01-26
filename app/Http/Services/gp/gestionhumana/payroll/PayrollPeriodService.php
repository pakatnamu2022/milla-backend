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
    $query = PayrollPeriod::with(['company']);

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

      if (!$period->canModify()) {
        throw new Exception('Cannot modify period: it is in ' . $period->status . ' status');
      }

      $period->update([
        'payment_date' => $data['payment_date'] ?? $period->payment_date,
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
        throw new Exception('Cannot delete period: it is not in OPEN status');
      }

      // Check if period has schedules or calculations
      if ($period->schedules()->exists() || $period->calculations()->exists()) {
        throw new Exception('Cannot delete period: it has associated schedules or calculations');
      }

      $period->delete();

      DB::commit();
      return response()->json(['message' => 'Period deleted successfully']);
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
      throw new Exception('No open period found');
    }

    return new PayrollPeriodResource($period->load('company'));
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
        throw new Exception('Cannot close period: it must be in APPROVED status');
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
        throw new Exception('Cannot set period to processing: invalid current status');
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
