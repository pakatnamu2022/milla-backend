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
    return new PayrollWorkTypeResource($this->find($id));
  }

  /**
   * Create a new work type
   */
  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();

      $workType = PayrollWorkType::create([
        'code' => strtoupper($data['code']),
        'name' => $data['name'],
        'description' => $data['description'] ?? null,
        'multiplier' => $data['multiplier'] ?? 1.0000,
        'base_hours' => $data['base_hours'] ?? 8,
        'is_extra_hours' => $data['is_extra_hours'] ?? false,
        'is_night_shift' => $data['is_night_shift'] ?? false,
        'is_holiday' => $data['is_holiday'] ?? false,
        'is_sunday' => $data['is_sunday'] ?? false,
        'active' => $data['active'] ?? true,
        'order' => $data['order'] ?? 0,
      ]);

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

      $workType->update([
        'code' => strtoupper($data['code'] ?? $workType->code),
        'name' => $data['name'] ?? $workType->name,
        'description' => $data['description'] ?? $workType->description,
        'multiplier' => $data['multiplier'] ?? $workType->multiplier,
        'base_hours' => $data['base_hours'] ?? $workType->base_hours,
        'is_extra_hours' => $data['is_extra_hours'] ?? $workType->is_extra_hours,
        'is_night_shift' => $data['is_night_shift'] ?? $workType->is_night_shift,
        'is_holiday' => $data['is_holiday'] ?? $workType->is_holiday,
        'is_sunday' => $data['is_sunday'] ?? $workType->is_sunday,
        'active' => $data['active'] ?? $workType->active,
        'order' => $data['order'] ?? $workType->order,
      ]);

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
