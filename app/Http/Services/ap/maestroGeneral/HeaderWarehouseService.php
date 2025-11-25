<?php

namespace App\Http\Services\ap\maestroGeneral;

use App\Http\Resources\ap\maestroGeneral\HeaderWarehouseResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\maestroGeneral\HeaderWarehouse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class HeaderWarehouseService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      HeaderWarehouse::class,
      $request,
      HeaderWarehouse::filters,
      HeaderWarehouse::sorts,
      HeaderWarehouseResource::class,
    );
  }

  public function find($id)
  {
    $HeaderWarehouse = HeaderWarehouse::where('id', $id)->first();
    if (!$HeaderWarehouse) {
      throw new Exception('Almacén padre de impuesto no encontrado');
    }
    return $HeaderWarehouse;
  }

  public function store(Mixed $data)
  {
    $HeaderWarehouse = HeaderWarehouse::create($data);
    return new HeaderWarehouseResource($HeaderWarehouse);
  }

  public function show($id)
  {
    return new HeaderWarehouseResource($this->find($id));
  }

  public function update(Mixed $data)
  {
    $HeaderWarehouse = $this->find($data['id']);
    $HeaderWarehouse->update($data);
    return new HeaderWarehouseResource($HeaderWarehouse);
  }

  public function destroy($id)
  {
    $HeaderWarehouse = $this->find($id);
    DB::transaction(function () use ($HeaderWarehouse) {
      $HeaderWarehouse->delete();
    });
    return response()->json(['message' => 'Almacén padre de impuesto eliminado correctamente']);
  }
}
