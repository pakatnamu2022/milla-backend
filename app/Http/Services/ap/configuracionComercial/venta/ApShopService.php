<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApShopResource;
use App\Http\Services\BaseService;
use App\Models\ap\ApCommercialMasters;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApShopService extends BaseService
{
  public function list(Request $request)
  {
    $shop_name = $request->query('search');
    $shops = ApCommercialMasters::where('type', 'TIENDA')
      ->when($shop_name, function ($query) use ($shop_name) {
        $query->where('description', 'like', '%' . $shop_name . '%');
      })
      ->with('sedes')
      ->get();
    return ApShopResource::collection($shops);
  }

  public function find($id)
  {
    $shop = ApCommercialMasters::where('type', 'TIENDA')
      ->with('sedes')
      ->find($id);

    if (!$shop) {
      throw new Exception('Tienda no encontrado');
    }
    return $shop;
  }

  public function store(Mixed $data)
  {
    DB::beginTransaction();

    try {
      $shop = ApCommercialMasters::create($data);

      $sedes_ids = $data['sedes'] ?? [];
      if (!empty($sedes_ids)) {
        Sede::whereIn('id', $sedes_ids)
          ->update(['shop_id' => $shop->id]);

        $shop->load('sedes');
      } else {
        throw new Exception('Debe asociar al menos una sede a la tienda');
      }

      DB::commit();
      return new ApShopResource($shop);

    } catch (\Exception $e) {
      DB::rollback();
      throw $e;
    }
  }

  public function show($id)
  {
    return new ApShopResource($this->find($id));
  }

  public function update(Mixed $data)
  {
    $shop = $this->find($data['id']);
    $shop->update($data);
    $sedes_ids = $data['sedes'] ?? [];

    if (isset($data['status'])) {
      $shop->update(['status' => $data['status']]);
      Sede::where('shop_id', $shop->id)
        ->update(['shop_id' => null]);
      $shop->load('sedes');
    } else {
      if (!empty($sedes_ids)) {
        Sede::where('shop_id', $shop->id)
          ->update(['shop_id' => null]);

        Sede::whereIn('id', $sedes_ids)
          ->update(['shop_id' => $shop->id]);

        $shop->load('sedes');
      } else {
        throw new Exception('Debe asociar al menos una sede a la tienda');
      }
    }

    return new ApShopResource($shop);
  }

  public function destroy($id)
  {
    $shop = $this->find($id);
    DB::transaction(function () use ($shop) {
      Sede::where('shop_id', $shop->id)
        ->update(['shop_id' => null]);
      $shop->delete();
    });
    return response()->json(['message' => 'Tienda eliminada correctamente']);
  }
}
