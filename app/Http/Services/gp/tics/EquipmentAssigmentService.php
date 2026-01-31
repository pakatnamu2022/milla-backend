<?php

namespace App\Http\Services\gp\tics;

use App\Http\Resources\gp\tics\EquipmentAssigmentResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\EquipmentAssigment;
use App\Models\gp\tics\EquipmentItemAssigment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipmentAssigmentService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    $query = EquipmentAssigment::query()->with(['worker', 'items.equipment.equipmentType']);

    return $this->getFilteredResults(
      $query,
      $request,
      EquipmentAssigment::filters,
      EquipmentAssigment::sorts,
      EquipmentAssigmentResource::class,
    );
  }

  public function store($data)
  {
    return DB::transaction(function () use ($data) {
      $items = $data['items'] ?? [];
      unset($data['items']);

      $assignment = EquipmentAssigment::create($data);

      foreach ($items as $item) {
        $item['asig_equipo_id'] = $assignment->id;
        EquipmentItemAssigment::create($item);
      }

      return new EquipmentAssigmentResource(
        EquipmentAssigment::with(['worker', 'items.equipment.equipmentType'])->find($assignment->id)
      );
    });
  }

  public function find($id)
  {
    $assignment = EquipmentAssigment::with(['worker', 'items.equipment.equipmentType'])->find($id);
    if (!$assignment) {
      throw new Exception('Asignación de equipo no encontrada');
    }
    return $assignment;
  }

  public function show($id)
  {
    return new EquipmentAssigmentResource($this->find($id));
  }

  public function update($data)
  {
    return DB::transaction(function () use ($data) {
      $assignment = $this->find($data['id']);
      $items = $data['items'] ?? null;
      unset($data['items']);

      $assignment->update($data);

      if ($items !== null) {
        $keepIds = collect($items)->pluck('id')->filter()->toArray();

        // Delete items not in the update
        $assignment->items()->whereNotIn('id', $keepIds)->delete();

        foreach ($items as $item) {
          if (!empty($item['id'])) {
            $assignment->items()->where('id', $item['id'])->update($item);
          } else {
            $item['asig_equipo_id'] = $assignment->id;
            EquipmentItemAssigment::create($item);
          }
        }
      }

      return new EquipmentAssigmentResource(
        EquipmentAssigment::with(['worker', 'items.equipment.equipmentType'])->find($assignment->id)
      );
    });
  }

  public function destroy($id)
  {
    $assignment = $this->find($id);
    $assignment->items()->delete();
    $assignment->delete();
    return response()->json(['message' => 'Asignación eliminada correctamente']);
  }

  public function historyByWorker($personaId)
  {
    $assignments = EquipmentAssigment::with(['worker', 'items.equipment.equipmentType'])
      ->where('persona_id', $personaId)
      ->orderBy('fecha', 'desc')
      ->get();

    return EquipmentAssigmentResource::collection($assignments);
  }

  public function historyByEquipment($equipoId)
  {
    $assignments = EquipmentAssigment::with(['worker', 'items.equipment.equipmentType'])
      ->whereHas('items', function ($query) use ($equipoId) {
        $query->where('equipo_id', $equipoId);
      })
      ->orderBy('fecha', 'desc')
      ->get();

    return EquipmentAssigmentResource::collection($assignments);
  }
}
