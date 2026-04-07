<?php

namespace App\Http\Services\gp\tics;

use App\Http\Resources\gp\tics\EquipmentAssigmentResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\EquipmentAssigment;
use App\Models\gp\tics\EquipmentItemAssigment;
use App\Models\gp\tics\PhoneLineWorker;
use Barryvdh\DomPDF\Facade\Pdf;
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

      // Validar que ningún equipo esté actualmente asignado.
      // status_deleted = false significa asignación activa (no desasignada).
      $equipmentIds = collect($items)->pluck('equipo_id')->toArray();

      $conflicting = EquipmentAssigment::where('status_deleted', 1)
        ->whereNull('unassigned_at')
        ->whereHas('items', fn($q) => $q->whereIn('equipo_id', $equipmentIds))
        ->with(['items' => fn($q) => $q->whereIn('equipo_id', $equipmentIds)->with('equipment')])
        ->first();

      if ($conflicting) {
        $name = $conflicting->items->first()?->equipment?->equipo ?? 'Equipo desconocido';
        throw new Exception("El equipo '{$name}' ya está asignado a '{$conflicting->worker->nombre_completo}' en la asignación '{$conflicting->id}'. Debe liberarlo antes de asignarlo nuevamente.");
      }

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

  public function confirm($id)
  {
    $assignment = $this->find($id);

    $assignment->update([
      'conformidad' => true,
      'fecha_conformidad' => now(),
    ]);

    return new EquipmentAssigmentResource(
      EquipmentAssigment::with(['worker', 'items.equipment.equipmentType'])->find($assignment->id)
    );
  }

  public function unassign($id, $data)
  {
    return DB::transaction(function () use ($id, $data) {
      $assignment = $this->find($id);

      // Si tenía una línea vinculada, desasignar también el phone_line_worker
      if ($assignment->phone_line_id) {
        PhoneLineWorker::where('phone_line_id', $assignment->phone_line_id)
          ->where('worker_id', $assignment->persona_id)
          ->where('active', true)
          ->update([
            'active'        => false,
            'unassigned_at' => $data['fecha'],
          ]);
      }

      $assignment->update([
        'status_deleted'       => true,
        'unassigned_at'        => $data['fecha'],
        'observacion_unassign' => $data['observacion_unassign'],
        'phone_line_id'        => null,
      ]);

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

  public function linkPhoneLine(int $id, ?int $phoneLineId)
  {
    return DB::transaction(function () use ($id, $phoneLineId) {
      $assignment = $this->find($id);

      if ($phoneLineId !== null) {
        // Validar que la línea no esté vinculada a otra asignación de equipo activa
        $conflictEquip = EquipmentAssigment::where('phone_line_id', $phoneLineId)
          ->where('status_deleted', false)
          ->whereNull('unassigned_at')
          ->where('id', '!=', $id)
          ->first();

        if ($conflictEquip) {
          throw new Exception("La línea ya está vinculada a la asignación de equipo #{$conflictEquip->id} del colaborador '{$conflictEquip->worker?->nombre_completo}'. Una línea no puede aparecer en más de una asignación activa.");
        }

        // Validar que la línea no esté activamente asignada a un trabajador diferente
        $conflictWorker = PhoneLineWorker::where('phone_line_id', $phoneLineId)
          ->where('active', true)
          ->where('worker_id', '!=', $assignment->persona_id)
          ->first();

        if ($conflictWorker) {
          throw new Exception("La línea ya está asignada activamente al colaborador '{$conflictWorker->worker?->nombre_completo}'. Una línea no puede vincularse a equipos de otro colaborador.");
        }

        // Crear phone_line_worker si no existe uno activo para este worker + línea
        $existingPlw = PhoneLineWorker::where('phone_line_id', $phoneLineId)
          ->where('worker_id', $assignment->persona_id)
          ->where('active', true)
          ->first();

        if (!$existingPlw) {
          // Auto-detectar el celular de la asignación (primer item tipo Celulares = tipo_equipo_id 3)
          $celularEquipoId = $assignment->items
            ->first(fn($item) => $item->equipment?->tipo_equipo_id === 3)
            ?->equipo_id;

          PhoneLineWorker::create([
            'phone_line_id' => $phoneLineId,
            'worker_id'     => $assignment->persona_id,
            'equipo_id'     => $celularEquipoId,
            'active'        => true,
            'assigned_at'   => now(),
          ]);
        }

        $assignment->update(['phone_line_id' => $phoneLineId]);
      } else {
        // Desvincular: también desasignar el phone_line_worker activo
        if ($assignment->phone_line_id) {
          PhoneLineWorker::where('phone_line_id', $assignment->phone_line_id)
            ->where('worker_id', $assignment->persona_id)
            ->where('active', true)
            ->update([
              'active'        => false,
              'unassigned_at' => now(),
            ]);
        }

        $assignment->update(['phone_line_id' => null]);
      }

      return new EquipmentAssigmentResource(
        EquipmentAssigment::with(['worker', 'items.equipment.equipmentType', 'phoneLine'])
          ->find($assignment->id)
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
      ->orderBy('status_deleted', 'desc')
      ->get();

    return EquipmentAssigmentResource::collection($assignments);
  }

  public function downloadAssignmentPdf($id)
  {
    $assignment = EquipmentAssigment::with(['worker.position', 'worker.area', 'worker.sede.company', 'items.equipment.equipmentType', 'writeUser'])
      ->findOrFail($id);

    $filename = "acta-asignacion_{$assignment->id}_{$assignment->fecha}.pdf";

    return Pdf::loadView('exports.equipment-assignment', compact('assignment'))
      ->download($filename);
  }

  public function downloadUnassignmentPdf($id)
  {
    $assignment = EquipmentAssigment::with(['worker.position', 'worker.area', 'worker.sede.company', 'items.equipment.equipmentType', 'writeUser'])
      ->findOrFail($id);

    $filename = "acta-devolucion_{$assignment->id}.pdf";

    return Pdf::loadView('exports.equipment-unassignment', compact('assignment'))
      ->download($filename);
  }
}
