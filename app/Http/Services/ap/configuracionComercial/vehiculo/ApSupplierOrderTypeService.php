<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApSupplierOrderTypeResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApSupplierOrderType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApSupplierOrderTypeService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApSupplierOrderType::class,
      $request,
      ApSupplierOrderType::filters,
      ApSupplierOrderType::sorts,
      ApSupplierOrderTypeResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApSupplierOrderType::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Tipo de pedido de proveedor no encontrado');
    }
    return $engineType;
  }

  public function store(array $data)
  {
    $engineType = ApSupplierOrderType::create($data);
    return new ApSupplierOrderTypeResource($engineType);
  }

  public function show($id)
  {
    return new ApSupplierOrderTypeResource($this->find($id));
  }

  public function update($data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new ApSupplierOrderTypeResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Tipo de pedido de proveedor eliminado correctamente']);
  }
}
