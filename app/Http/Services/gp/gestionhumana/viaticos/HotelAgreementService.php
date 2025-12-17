<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Services\BaseService;
use App\Http\Resources\gp\gestionhumana\viaticos\HotelAgreementResource;
use App\Models\gp\gestionhumana\viaticos\HotelAgreement;
use Illuminate\Http\Request;

class HotelAgreementService extends BaseService
{
  public function index(Request $request)
  {
    return $this->getFilteredResults(
      HotelAgreement::query(),
      $request,
      HotelAgreement::filters,
      HotelAgreement::sorts,
      HotelAgreementResource::class,
    );
  }

  public function active(Request $request)
  {
    return $this->getFilteredResults(
      HotelAgreement::active(),
      $request,
      HotelAgreement::filters,
      HotelAgreement::sorts,
      HotelAgreementResource::class,
    );
  }

  public function store(array $data): HotelAgreement
  {
    return HotelAgreement::create($data);
  }

  public function show(int $id): ?HotelAgreement
  {
    return HotelAgreement::find($id);
  }

  public function update(int $id, array $data): HotelAgreement
  {
    $agreement = HotelAgreement::findOrFail($id);
    $agreement->update($data);
    return $agreement->fresh();
  }

  public function destroy(int $id): bool
  {
    $agreement = HotelAgreement::findOrFail($id);

    if ($agreement->reservations()->exists()) {
      throw new \Exception('No se puede eliminar el convenio porque tiene reservaciones asociadas.');
    }

    return $agreement->delete();
  }
}
