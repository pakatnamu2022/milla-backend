<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApAssignSedeResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\venta\ApAssignSede;
use App\Models\gp\gestionsistema\Sede;
use Illuminate\Http\Request;
use Exception;

class ApAssignSedeService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Sede::with('asesores'), // 👈 base query sobre sedes + asesores
      $request,
      Sede::filters, // puedes definir filtros en el modelo Sede
      Sede::sorts,
      ApAssignSedeResource::class // 👈 nuevo resource que incluye asesores
    );
  }

  public function show($id)
  {
    $sede = Sede::with('asesores')->find($id);
    if (!$sede) {
      throw new Exception('Sede no encontrada');
    }
    return new ApAssignSedeResource($sede);
  }

  public function store(array $data)
  {
    $sede = Sede::findOrFail($data['sede_id']);
    $sede->asesores()->sync($data['asesores']); // 👈 sin tipo en el pivote

    return new ApAssignSedeResource($sede->load('asesores'));
  }
  
  public function update(mixed $data)
  {
    $sede = Sede::findOrFail($data['sede_id']);
    $sede->asesores()->sync($data['asesores']); // 👈 igual que store

    return new ApAssignSedeResource($sede->load('asesores'));
  }
}
