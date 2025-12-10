<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\WorkOrderItemResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\taller\ApWorkOrderItem;
use Exception;
use Illuminate\Http\Request;

class WorkOrderItemService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApWorkOrderItem::class,
      $request,
      ApWorkOrderItem::filters,
      ApWorkOrderItem::sorts,
      WorkOrderItemResource::class);
  }

  public function find($id)
  {
    $item = ApWorkOrderItem::with(['workOrder', 'typePlanning'])
      ->where('id', $id)
      ->first();

    if (!$item) {
      throw new Exception('Item de orden de trabajo no encontrado');
    }

    return $item;
  }

  public function store(mixed $data)
  {
    $item = ApWorkOrderItem::create($data);
    return new WorkOrderItemResource($item->load(['workOrder', 'typePlanning']));
  }

  public function show($id)
  {
    return new WorkOrderItemResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $item = $this->find($data['id']);
    $item->update($data);
    return new WorkOrderItemResource($item->fresh(['workOrder', 'typePlanning']));
  }

  public function destroy($id)
  {
    $item = $this->find($id);
    $item->delete();
    return response()->json(['message' => 'Item de orden de trabajo eliminado correctamente']);
  }
}