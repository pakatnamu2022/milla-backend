<?php

namespace App\Http\Services\ap\maestroGeneral;

use App\Http\Resources\ap\maestroGeneral\WarehouseResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\maestroGeneral\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class WarehouseService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Warehouse::class,
      $request,
      Warehouse::filters,
      Warehouse::sorts,
      WarehouseResource::class,
    );
  }

  public function getMyWarehouses(Request $request)
  {
    $sede = $request->user()->person->sede_id;
    if (!$sede) {
      throw new Exception('El usuario no tiene una sede asignada');
    };
    $warehouse = Warehouse::where('sede_id', $sede)
      ->where('type_operation_id', 794)
      ->where('status', 1)
      ->orderBy('dyn_code', 'asc')
      ->get();
    return WarehouseResource::collection($warehouse);
  }

  public function find($id)
  {
    $Warehouse = Warehouse::where('id', $id)->first();
    if (!$Warehouse) {
      throw new Exception('Almacén no encontrado');
    }
    return $Warehouse;
  }

  public function store(mixed $data)
  {
    $Warehouse = Warehouse::create($data);
    return new WarehouseResource($Warehouse);
  }

  public function show($id)
  {
    return new WarehouseResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $Warehouse = $this->find($data['id']);
    $Warehouse->update($data);
    return new WarehouseResource($Warehouse);
  }

  public function destroy($id)
  {
    $Warehouse = $this->find($id);
    DB::transaction(function () use ($Warehouse) {
      $Warehouse->delete();
    });
    return response()->json(['message' => 'Almacén eliminado correctamente']);
  }
}
