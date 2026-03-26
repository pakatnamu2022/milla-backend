<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\TypePlanningWorkOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class TypePlanningWorkOrderService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      TypePlanningWorkOrder::class,
      $request,
      TypePlanningWorkOrder::filters,
      TypePlanningWorkOrder::sorts,
      TypePlanningWorkOrderResource::class,
    );
  }

  public function find($id)
  {
    $typePlanningWorkOrder = TypePlanningWorkOrder::where('id', $id)->first();
    if (!$typePlanningWorkOrder) {
      throw new Exception('Tipo de planificación no encontrado');
    }
    return $typePlanningWorkOrder;
  }

  public function store(mixed $data)
  {
    $typePlanningWorkOrder = TypePlanningWorkOrder::create($data);
    return new TypePlanningWorkOrderResource($typePlanningWorkOrder);
  }

  public function show($id)
  {
    return new TypePlanningWorkOrderResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $typePlanningWorkOrder = $this->find($data['id']);
    $typePlanningWorkOrder->update($data);
    return new TypePlanningWorkOrderResource($typePlanningWorkOrder);
  }

  public function destroy($id)
  {
    $typePlanningWorkOrder = $this->find($id);
    DB::transaction(function () use ($typePlanningWorkOrder) {
      $typePlanningWorkOrder->delete();
    });
    return response()->json(['message' => 'Tipo de planificación eliminada correctamente.']);
  }
}
