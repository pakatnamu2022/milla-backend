<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollWorkTypeSegmentResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\payroll\PayrollWorkTypeSegment;
use Exception;
use Illuminate\Http\Request;

class PayrollWorkTypeSegmentService extends BaseService
{
  /**
   * Get all segments for a work type
   */
  public function listByWorkType(int $workTypeId)
  {
    $segments = PayrollWorkTypeSegment::where('work_type_id', $workTypeId)
      ->ordered()
      ->get();

    return PayrollWorkTypeSegmentResource::collection($segments);
  }

  /**
   * Store a new segment
   */
  public function store(array $data)
  {
    $segment = PayrollWorkTypeSegment::create($data);
    return new PayrollWorkTypeSegmentResource($segment);
  }

  /**
   * Update a segment
   */
  public function update(array $data)
  {
    $segment = PayrollWorkTypeSegment::findOrFail($data['id']);
    $segment->update($data);
    return new PayrollWorkTypeSegmentResource($segment->fresh());
  }

  /**
   * Delete a segment
   */
  public function destroy(int $id)
  {
    $segment = PayrollWorkTypeSegment::findOrFail($id);
    $segment->delete();
    return ['message' => 'Segment deleted successfully'];
  }
}
