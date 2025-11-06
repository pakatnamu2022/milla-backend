<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\BusinessPartnersEstablishmentResource;
use App\Http\Services\BaseService;
use App\Models\ap\comercial\BusinessPartnersEstablishment;
use App\Models\gp\gestionsistema\District;
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

  public function store(mixed $data)
  {
    // Si se envía district_id, buscar la información completa de ubicación
    if (!empty($data['district_id'])) {
      $district = District::where('id', $data['district_id'])
        ->with(['province.department'])
        ->first();

      if ($district) {
        $locationParts = [
          $district->name,
          $district->province->name ?? null,
          $district->province->department->name ?? null,
        ];

        // Filtrar valores nulos y concatenar
        $location = implode(' - ', array_filter($locationParts));

        // Concatenar dirección con ubicación para formar full_address
        $data['full_address'] = trim(($data['address'] ?? '') . ' - ' . $location);

        // Asignar ubigeo del distrito
        $data['ubigeo'] = $district->ubigeo;
      }
    }

    $establishment = BusinessPartnersEstablishment::create($data);
    return new BusinessPartnersEstablishmentResource($establishment);
  }

  public function show($id)
  {
    return new BusinessPartnersEstablishmentResource($this->find($id));
  }

  public function update(mixed $data)
  {
    DB::beginTransaction();
    try {
      $establishment = $this->find($data['id']);

      // Si se envía district_id, buscar la información completa de ubicación
      if (!empty($data['district_id'])) {
        $district = District::where('id', $data['district_id'])
          ->with(['province.department'])
          ->first();

        if ($district) {
          $locationParts = [
            $district->name,
            $district->province->name ?? null,
            $district->province->department->name ?? null,
          ];

          // Filtrar valores nulos y concatenar
          $location = implode(' - ', array_filter($locationParts));

          // Concatenar dirección con ubicación para formar full_address
          $data['full_address'] = trim(($data['address'] ?? $establishment->address ?? '') . ' - ' . $location);

          // Asignar ubigeo del distrito
          $data['ubigeo'] = $district->ubigeo;
        }
      }

      $establishment->update($data);
      DB::commit();
      return new BusinessPartnersEstablishmentResource($establishment);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function destroy($id)
  {
    $establishment = $this->find($id);
    DB::transaction(function () use ($establishment) {
      $establishment->delete();
    });
    return response()->json(['message' => 'establecimiento eliminado correctamente']);
  }
}
