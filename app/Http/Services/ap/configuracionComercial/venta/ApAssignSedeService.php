<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApAssignSedeResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\venta\ApAssignSedePeriodo;
use App\Models\gp\gestionsistema\Sede;
use Illuminate\Http\Request;
use Exception;

class ApAssignSedeService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Sede::with('asesores'),
      $request,
      Sede::filters,
      Sede::sorts,
      ApAssignSedeResource::class
    );
  }

  public function listRecord(Request $request)
  {
    $anio = $request->query('anio');
    $month = $request->query('month');
    $sede = $request->query('sede');

    $query = ApAssignSedePeriodo::with(['sede', 'asesor'])
      ->when($anio, fn($q) => $q->where('anio', $anio))
      ->when($month, fn($q) => $q->where('month', $month))
      ->when($sede, function ($q) use ($sede) {
        $q->whereHas('sede', function ($sub) use ($sede) {
          $sub->where('abreviatura', 'like', "%{$sede}%");
        });
      });

    $periodos = $query->get();

    $grouped = $periodos->groupBy('sede_id')->map(function ($items) {
      $first = $items->first();

      return [
        'sede_id' => $first->sede_id,
        'sede' => $first->sede->abreviatura,
        'anio' => $first->anio,
        'month' => $first->month,
        'asesores' => $items->map(function ($item) {
          return [
            'id' => $item->asesor->id,
            'name' => $item->asesor->nombre_completo,
          ];
        })->values(),
      ];
    })->values();

    return response()->json(['data' => $grouped]);
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
    $sede->asesores()->sync($data['asesores']);

    return new ApAssignSedeResource($sede->load('asesores'));
  }

  public function update(mixed $data)
  {
    $sede = Sede::findOrFail($data['sede_id']);
    $sede->asesores()->sync($data['asesores']);

    //si se editan registros de ese periodo determinado se debe editar tambien en la tabla ap_assign_sede_periodo
    if (isset($data['anio']) && isset($data['month'])) {
      ApAssignSedePeriodo::where('sede_id', $data['sede_id'])
        ->where('anio', $data['anio'])
        ->where('month', $data['month'])
        ->delete();

      $insertData = [];
      foreach ($data['asesores'] as $asesorId) {
        $insertData[] = [
          'sede_id' => $data['sede_id'],
          'asesor_id' => $asesorId,
          'anio' => $data['anio'],
          'month' => $data['month'],
        ];
      }
      if (!empty($insertData)) {
        ApAssignSedePeriodo::insert($insertData);
      }
    }

    return new ApAssignSedeResource($sede->load('asesores'));
  }
}
