<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApGoalSellOutInResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\configuracionComercial\venta\ApGoalSellOutIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApGoalSellOutInService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApGoalSellOutIn::class,
      $request,
      ApGoalSellOutIn::filters,
      ApGoalSellOutIn::sorts,
      ApGoalSellOutInResource::class,
    );
  }

  public function find($id)
  {
    $ApGoalSellOutIn = ApGoalSellOutIn::where('id', $id)->first();
    if (!$ApGoalSellOutIn) {
      throw new Exception('Registro no encontrado');
    }
    return $ApGoalSellOutIn;
  }

  public function store(mixed $data)
  {
    // validamos que no exista un registro con el mismo year, month, type, brand_id y shop_id
    $exists = ApGoalSellOutIn::where('year', $data['year'])
      ->where('month', $data['month'])
      ->where('type', $data['type'])
      ->where('brand_id', $data['brand_id'])
      ->where('shop_id', $data['shop_id'])
      ->first();
    if ($exists) {
      throw new Exception('Ya existe un registro con el mismo año, mes, tipo, marca y tienda');
    }
    $ApGoalSellOutIn = ApGoalSellOutIn::create($data);
    return new ApGoalSellOutInResource($ApGoalSellOutIn);
  }

  public function show($id)
  {
    return new ApGoalSellOutInResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $ApGoalSellOutIn = $this->find($data['id']);
    $ApGoalSellOutIn->update($data);
    return new ApGoalSellOutInResource($ApGoalSellOutIn);
  }

  public function destroy($id)
  {
    $ApGoalSellOutIn = $this->find($id);
    DB::transaction(function () use ($ApGoalSellOutIn) {
      $ApGoalSellOutIn->delete();
    });
    return response()->json(['message' => 'Registro eliminado correctamente']);
  }
}
