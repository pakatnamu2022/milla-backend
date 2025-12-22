<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Services\BaseService;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemRateResource;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionhumana\viaticos\PerDiemRate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerDiemRateService extends BaseService
{
  public function index(Request $request)
  {
    return $this->getFilteredResults(
      PerDiemRate::class,
      $request,
      PerDiemRate::filters,
      PerDiemRate::sorts,
      PerDiemRateResource::class,
    );
  }

  public function find($id)
  {
    $perDiemRate = PerDiemRate::where('id', $id)->first();
    if (!$perDiemRate) {
      throw new Exception('Tarifa de viático no encontrada');
    }
    return $perDiemRate;
  }

  public function store(mixed $data)
  {
    $rate = PerDiemRate::create($data);
    return new PerDiemRateResource($rate);
  }

  public function show($id)
  {
    return new PerDiemRateResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $rate = $this->find($data['id']);
    $rate->update($data);
    return new PerDiemRateResource($rate);
  }

  public function destroy($id)
  {
    $perDiemRate = $this->find($id);
    DB::transaction(function () use ($perDiemRate) {
      $perDiemRate->delete();
    });
    return response()->json(['message' => 'Tarifa de viático eliminada correctamente']);
  }
}
