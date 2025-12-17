<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Services\BaseService;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRateResource;
use App\Models\gp\gestionhumana\viaticos\PerDiemRate;
use Illuminate\Http\Request;

class PerDiemRateService extends BaseService
{
  public function index(Request $request)
  {
    return $this->getFilteredResults(
      PerDiemRate::with(['policy', 'district', 'category', 'expenseType']),
      $request,
      PerDiemRate::filters,
      PerDiemRate::sorts,
      PerDiemRateResource::class,
    );
  }

  public function store(array $data): PerDiemRate
  {
    return PerDiemRate::create($data);
  }

  public function show(int $id): ?PerDiemRate
  {
    return PerDiemRate::with(['policy', 'district', 'category', 'expenseType'])->find($id);
  }

  public function update(int $id, array $data): PerDiemRate
  {
    $rate = PerDiemRate::findOrFail($id);
    $rate->update($data);
    return $rate->fresh(['policy', 'district', 'category', 'expenseType']);
  }

  public function destroy(int $id): bool
  {
    $rate = PerDiemRate::findOrFail($id);
    return $rate->delete();
  }
}
