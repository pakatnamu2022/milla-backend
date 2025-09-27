<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\PotentialBuyersResource;
use App\Http\Services\BaseService;
use App\Models\ap\comercial\PotentialBuyers;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PotentialBuyersService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PotentialBuyers::class,
      $request,
      PotentialBuyers::filters,
      PotentialBuyers::sorts,
      PotentialBuyersResource::class,
    );
  }

  public function find($id)
  {
    $businessPartner = PotentialBuyers::where('id', $id)->first();
    if (!$businessPartner) {
      throw new Exception('Cliente potencial no encontrado');
    }
    return $businessPartner;
  }

  public function store(mixed $data)
  {
    DB::beginTransaction();
    try {
      $businessPartner = PotentialBuyers::create($data);
      DB::commit();
      return new PotentialBuyersResource($businessPartner);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function show($id)
  {
    return new PotentialBuyersResource($this->find($id));
  }

  public function destroy($id)
  {
    DB::beginTransaction();
    try {
      $businessPartner = $this->find($id);
      $businessPartner->delete();
      DB::commit();
      return response()->json(['message' => 'Cliente potencial eliminado correctamente']);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }
}
