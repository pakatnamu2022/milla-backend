<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\BusinessPartnersEstablishmentResource;
use App\Http\Services\BaseService;
use App\Models\ap\comercial\BusinessPartnersEstablishment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessPartnersEstablishmentService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      BusinessPartnersEstablishment::class,
      $request,
      BusinessPartnersEstablishment::filters,
      BusinessPartnersEstablishment::sorts,
      BusinessPartnersEstablishmentResource::class,
    );
  }

  public function find($id)
  {
    $businessPartner = BusinessPartnersEstablishment::where('id', $id)->first();
    if (!$businessPartner) {
      throw new Exception('Establecimiento no encontrado');
    }
    return $businessPartner;
  }

  public function update(mixed $data)
  {
    DB::beginTransaction();
    try {
      $establishment = $this->find($data['id']);
      $establishment->update($data);
      DB::commit();
      return new BusinessPartnersEstablishmentResource($establishment);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }
}
