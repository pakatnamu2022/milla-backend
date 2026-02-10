<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollWorkTypeResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\PayrollWorkType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollWorkTypeService extends BaseService implements BaseServiceInterface
{
  /**
   * Get all work types with filters and pagination
   */
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PayrollWorkType::class,
      $request,
      PayrollWorkType::filters,
      PayrollWorkType::sorts,
      PayrollWorkTypeResource::class,
    );
  }

  /**
   * Find a work type by ID
   */
  public function find($id)
  {
    $workType = PayrollWorkType::find($id);
    if (!$workType) {
      throw new Exception('Work type not found');
    }
    return $workType;
  }

  /**
   * Show a work type by ID
   */
  public function show($id)
  {
    $workType = $this->find($id);
    $workType->load('segments'); // Load related schedules
    return new PayrollWorkTypeResource($workType);
  }

  /**
   * Create a new work type
   */
  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();
      $workType = PayrollWorkType::create($data);
      DB::commit();
      return new PayrollWorkTypeResource($workType);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Update a work type
   */
  public function update(mixed $data)
  {
    try {
      DB::beginTransaction();

      $workType = $this->find($data['id']);
      $workType->update($data);
      DB::commit();
      return new PayrollWorkTypeResource($workType->fresh());
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Delete a work type
   */
  public function destroy($id)
  {
    try {
      DB::beginTransaction();

      $workType = $this->find($id);

      // Check if work type is in use
      if ($workType->schedules()->exists()) {
        throw new Exception('Cannot delete work type: it is being used in schedules');
      }

      $workType->delete();

      DB::commit();
      return response()->json(['message' => 'Work type deleted successfully']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
