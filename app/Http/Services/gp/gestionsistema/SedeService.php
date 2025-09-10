<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\SedeResource;
use App\Http\Resources\gp\tics\EquipmentResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\Sede;
use Exception;
use Illuminate\Http\Request;

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

  public function getWorkers(Request $request)
  {
    $sedeId = $request->input('sede_id');

    if (!$sedeId) {
      throw new \InvalidArgumentException("Debe enviar un 'sede_id'");
    }

    $sede = Sede::with('workers')->findOrFail($sedeId);

    return $sede->workers->map(fn($worker) => [
      'id' => $worker->id,
      'name' => $worker->nombre_completo,
    ]);
  }

}
