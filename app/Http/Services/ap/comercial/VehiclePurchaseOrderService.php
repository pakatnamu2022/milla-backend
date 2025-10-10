<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\VehiclePurchaseOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehiclePurchaseOrderService extends BaseService implements BaseServiceInterface
{

  protected VehiclePurchaseOrder $model;

  public function __construct(VehiclePurchaseOrder $model)
  {
    $this->model = $model;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      $this->model,
      $request,
      $this->model->filters,
      $this->model->sorts,
      VehiclePurchaseOrderResource::class
    );
  }

  public function find($id)
  {
    $model = $this->model->where('id', $id)->first();
    if (!$model) {
      throw new Exception('Orden de compra de vehículo no encontrada');
    }
    return $model;
  }

  public function store(mixed $data)
  {
    $data['ap_vehicle_status_id'] = 28;
    $model = $this->model->create($data);
    return new VehiclePurchaseOrderResource($model);
  }

  public function show($id)
  {
    return new VehiclePurchaseOrderResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $model = $this->find($data['id']);
    $model->update($data);
    return new VehiclePurchaseOrderResource($model);
  }

  public function destroy($id)
  {
    $model = $this->find($id);
    DB::transaction(function () use ($model) {
      $model->delete();
    });
    return response()->json(['message' => 'Orden de compra de vehículo eliminada correctamente']);
  }
}
