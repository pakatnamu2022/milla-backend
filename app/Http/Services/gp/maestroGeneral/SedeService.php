<?php

namespace App\Http\Services\gp\maestroGeneral;

use App\Http\Resources\gp\maestroGeneral\SedeResource;
use App\Http\Services\BaseService;
use App\Models\gp\maestroGeneral\Sede;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SedeService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Sede::where('status_deleted', 1)->whereNotNull('empresa_id')->orderBy('empresa_id', 'asc'),
      $request,
      Sede::filters,
      Sede::sorts,
      SedeResource::class,
    );
  }

  public function getMySedes(Request $request)
  {
    $user = $request->user();
    $sedes = $user->sedes;
    return SedeResource::collection($sedes);
  }

  public function getAvailableLocationsShop(Request $request)
  {
    return $this->getFilteredResults(
      Sede::where('status_deleted', 1)
        ->whereNotNull('empresa_id')
        ->where('status', 1)
        ->whereNull('shop_id')
        ->orderBy('empresa_id', 'asc'),
      $request,
      Sede::filters,
      Sede::sorts,
      SedeResource::class,
    );
  }

  public function find($id)
  {
    $Sede = Sede::where('id', $id)->first();
    if (!$Sede) {
      throw new Exception('Sede no encontrado');
    }
    return $Sede;
  }

  public function store(mixed $data)
  {
    $Sede = Sede::create($data);
    return new SedeResource($Sede);
  }

  public function show($id)
  {
    return new SedeResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $Sede = $this->find($data['id']);
    $Sede->update($data);
    return new SedeResource($Sede);
  }

  public function destroy($id)
  {
    $Sede = $this->find($id);
    DB::transaction(function () use ($Sede) {
      $Sede->delete();
    });
    return response()->json(['message' => 'Sede eliminado correctamente']);
  }
}
