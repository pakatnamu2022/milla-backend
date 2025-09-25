<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\BusinessPartnersResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\BusinessPartners;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessPartnersService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      BusinessPartners::class,
      $request,
      BusinessPartners::filters,
      BusinessPartners::sorts,
      BusinessPartnersResource::class,
    );
  }

  public function find($id)
  {
    $businessPartner = BusinessPartners::where('id', $id)->first();
    if (!$businessPartner) {
      throw new Exception('Socio comercial no encontrado');
    }
    return $businessPartner;
  }

  public function store(mixed $data)
  {
    DB::beginTransaction();
    try {
      $businessPartner = BusinessPartners::create($data);
      DB::commit();
      return new BusinessPartnersResource($businessPartner);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception('Error al crear el socio comercial: ' . $e->getMessage());
    }
  }

  public function show($id)
  {
    return new BusinessPartnersResource($this->find($id));
  }

  public function update(mixed $data)
  {
    DB::beginTransaction();
    try {
      $businessPartner = $this->find($data['id']);
      $businessPartner->update($data);
      DB::commit();
      return new BusinessPartnersResource($businessPartner);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception('Error al actualizar el socio comercial: ' . $e->getMessage());
    }
  }

  public function destroy($id)
  {
    DB::beginTransaction();
    try {
      $businessPartner = $this->find($id);
      $businessPartner->delete();
      DB::commit();
      return response()->json(['message' => 'Socio comercial eliminado correctamente']);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception('Error al eliminar el socio comercial: ' . $e->getMessage());
    }
  }
}
