<?php

namespace App\Http\Services\gp\tics;

use App\Http\Resources\gp\tics\EquipmentAssigmentResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\EquipmentAssigment;
use App\Models\gp\tics\EquipmentItemAssigment;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

//      FOR WORKER
      EquipmentAssigment::where('persona_id', $data['persona_id'])
        ->where('status_deleted', true)
        ->update(['status_deleted' => false, 'unassigned_at' => now()]);

//      FOR EQUIPMENT
      EquipmentAssigment::where('status_deleted', true)
        ->whereHas('items', function ($query) use ($items) {
          $equipmentIds = collect($items)->pluck('equipo_id')->toArray();
          $query->whereIn('equipo_id', $equipmentIds);
        })
        ->update(['status_deleted' => false, 'unassigned_at' => now()]);

      $assignment = EquipmentAssigment::create($data);

      foreach ($items as $item) {
        $item['asig_equipo_id'] = $assignment->id;
        EquipmentItemAssigment::create($item);
      }

      $assignment = EquipmentAssigment::with(['worker.position', 'worker.area', 'items.equipment.equipmentType'])->find($assignment->id);

      $pdfPath = $this->generateAssignmentPdf($assignment);
      $assignment->update(['pdf_path' => $pdfPath]);

      return new EquipmentAssigmentResource($assignment);
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

      $assignment->update([
        'status_deleted' => true,
        'unassigned_at' => $data['fecha'],
        'observacion_unassign' => $data['observacion_unassign'],
      ]);

      $assignment = EquipmentAssigment::with(['worker.position', 'worker.area', 'items.equipment.equipmentType'])->find($assignment->id);

      $pdfPath = $this->generateUnassignmentPdf($assignment);
      $assignment->update(['pdf_unassign_path' => $pdfPath]);

      return new EquipmentAssigmentResource($assignment);
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
      ->orderBy('status_deleted', 'desc')
      ->get();

    return EquipmentAssigmentResource::collection($assignments);
  }

  private function generateAssignmentPdf(EquipmentAssigment $assignment): string
  {
    $pdf = Pdf::loadView('exports.equipment-assignment', compact('assignment'));
    $filename = "assignment_{$assignment->id}_{$assignment->fecha}.pdf";
    $path = "equipment-assignments/{$filename}";

    Storage::disk('local')->put($path, $pdf->output());

    return $path;
  }

  private function generateUnassignmentPdf(EquipmentAssigment $assignment): string
  {
    $pdf = Pdf::loadView('exports.equipment-unassignment', compact('assignment'));
    $filename = "unassignment_{$assignment->id}_{$assignment->unassigned_at}.pdf";
    $path = "equipment-assignments/{$filename}";

    Storage::disk('local')->put($path, $pdf->output());

    return $path;
  }
}
