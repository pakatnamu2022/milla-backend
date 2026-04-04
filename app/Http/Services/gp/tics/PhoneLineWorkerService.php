<?php

namespace App\Http\Services\gp\tics;

use App\Http\Resources\gp\tics\PhoneLineWorkerResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\Equipment;
use App\Models\gp\tics\EquipmentAssigment;
use App\Models\gp\tics\PhoneLineWorker;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PhoneLineWorkerService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PhoneLineWorker::query()->with('equipment'),
      $request,
      PhoneLineWorker::filters,
      PhoneLineWorker::sorts,
      PhoneLineWorkerResource::class,
    );
  }

  public function history($phoneLineId)
  {
    $assignments = PhoneLineWorker::with('equipment')
      ->where('phone_line_id', $phoneLineId)
      ->orderBy('assigned_at', 'desc')
      ->get();
    return PhoneLineWorkerResource::collection($assignments);
  }

  public function store($data)
  {
    return DB::transaction(function () use ($data) {
      // Validar que la línea no esté ya asignada a alguien más
      $lineActive = PhoneLineWorker::where('phone_line_id', $data['phone_line_id'])
        ->where('active', true)
        ->exists();

      if ($lineActive) {
        throw new Exception('La línea telefónica ya está asignada. Debe liberarla antes de asignarla nuevamente.');
      }

      // Validar que el trabajador no tenga ya una línea activa
      $workerActive = PhoneLineWorker::where('worker_id', $data['worker_id'])
        ->where('active', true)
        ->exists();

      if ($workerActive) {
        throw new Exception('El trabajador ya tiene una línea telefónica activa. Una persona no puede tener más de una línea asignada.');
      }

      // Si el trabajador ya tuvo alguna línea antes (historial), la nueva debe ir asociada a un equipo
      $hasHistory = PhoneLineWorker::where('worker_id', $data['worker_id'])->exists();

      if ($hasHistory && empty($data['equipo_id'])) {
        throw new Exception('El trabajador ya tiene historial de líneas telefónicas. Toda nueva asignación debe ir asociada a un equipo para evitar líneas sueltas.');
      }

      // Validar que el equipo sea un celular y no esté ya vinculado a otra línea activa
      if (!empty($data['equipo_id'])) {
        $this->validateEquipoParaLinea($data['equipo_id']);
      }

      $phoneLineWorker = PhoneLineWorker::create($data);

      // Sincronizar: si viene con equipo, actualizar el assignment de equipo correspondiente
      $this->syncLineToEquipmentAssigment($phoneLineWorker);

      return new PhoneLineWorkerResource(PhoneLineWorker::with('equipment')->find($phoneLineWorker->id));
    });
  }

  public function find($id)
  {
    $phoneLineWorker = PhoneLineWorker::with('equipment')->where('id', $id)->first();
    if (!$phoneLineWorker) {
      throw new Exception('Asignación de línea telefónica no encontrada');
    }
    return $phoneLineWorker;
  }

  public function show($id)
  {
    return new PhoneLineWorkerResource($this->find($id));
  }

  public function unassign($id, $data)
  {
    return DB::transaction(function () use ($id, $data) {
      $phoneLineWorker = $this->find($id);

      if (!$phoneLineWorker->active) {
        throw new Exception('La línea telefónica ya está desasignada.');
      }

      $phoneLineWorker->update([
        'active'               => false,
        'unassigned_at'        => $data['unassigned_at'],
        'observacion_unassign' => $data['observacion_unassign'],
      ]);

      // Sincronizar: limpiar FK en el assignment de equipo correspondiente
      $this->clearLineFromEquipmentAssigment($phoneLineWorker->phone_line_id, $phoneLineWorker->worker_id);

      return new PhoneLineWorkerResource(PhoneLineWorker::with('equipment')->find($phoneLineWorker->id));
    });
  }

  public function update($data)
  {
    $phoneLineWorker = $this->find($data['id']);
    $phoneLineWorker->update($data);
    return new PhoneLineWorkerResource(PhoneLineWorker::with('equipment')->find($phoneLineWorker->id));
  }

  public function linkEquipment(int $id, ?int $equipoId)
  {
    return DB::transaction(function () use ($id, $equipoId) {
      $phoneLineWorker = $this->find($id);
      $oldEquipoId = $phoneLineWorker->equipo_id;

      if ($equipoId !== null) {
        $this->validateEquipoParaLinea($equipoId, $id);
      }

      // Limpiar FK del assignment anterior si el equipo cambió
      if ($oldEquipoId && $oldEquipoId !== $equipoId) {
        EquipmentAssigment::where('persona_id', $phoneLineWorker->worker_id)
          ->where('phone_line_id', $phoneLineWorker->phone_line_id)
          ->whereHas('items', fn($q) => $q->where('equipo_id', $oldEquipoId))
          ->update(['phone_line_id' => null]);
      }

      $phoneLineWorker->update(['equipo_id' => $equipoId]);
      $phoneLineWorker->refresh();

      // Sincronizar con el nuevo assignment
      $this->syncLineToEquipmentAssigment($phoneLineWorker);

      return new PhoneLineWorkerResource(
        PhoneLineWorker::with('equipment')->find($phoneLineWorker->id)
      );
    });
  }

  /**
   * Valida que el equipo sea de tipo Celular (tipo_equipo_id = 3)
   * y que no esté ya vinculado a otra línea activa.
   */
  private function validateEquipoParaLinea(int $equipoId, ?int $excludePhoneLineWorkerId = null): void
  {
    $equipo = Equipment::find($equipoId);

    if (!$equipo) {
      throw new Exception('El equipo no existe.');
    }

    if ($equipo->tipo_equipo_id !== 3) {
      throw new Exception("Solo se pueden vincular celulares a una línea telefónica. El equipo '{$equipo->equipo}' es de otro tipo.");
    }

    $conflict = PhoneLineWorker::where('equipo_id', $equipoId)
      ->where('active', true)
      ->when($excludePhoneLineWorkerId, fn($q) => $q->where('id', '!=', $excludePhoneLineWorkerId))
      ->first();

    if ($conflict) {
      throw new Exception("El equipo '{$equipo->equipo}' ya está vinculado a la línea '{$conflict->phoneLine?->line_number}' asignada a '{$conflict->worker?->nombre_completo}'. Un equipo no puede tener más de una línea.");
    }
  }

  /**
   * Busca el EquipmentAssigment activo de este worker que tenga el equipo en sus items
   * y actualiza su phone_line_id para que apunte a la línea asignada.
   */
  private function syncLineToEquipmentAssigment(PhoneLineWorker $plw): void
  {
    if (!$plw->equipo_id) return;

    $assignment = EquipmentAssigment::where('persona_id', $plw->worker_id)
      ->where('status_deleted', false)
      ->whereNull('unassigned_at')
      ->whereHas('items', fn($q) => $q->where('equipo_id', $plw->equipo_id))
      ->first();

    if ($assignment) {
      $assignment->update(['phone_line_id' => $plw->phone_line_id]);
    }
  }

  /**
   * Limpia el phone_line_id del EquipmentAssigment activo que tenga esa línea.
   */
  private function clearLineFromEquipmentAssigment(int $phoneLineId, int $workerId): void
  {
    EquipmentAssigment::where('persona_id', $workerId)
      ->where('phone_line_id', $phoneLineId)
      ->update(['phone_line_id' => null]);
  }

  public function destroy($id)
  {
    $phoneLineWorker = $this->find($id);
    $phoneLineWorker->delete();
    return response()->json(['message' => 'Asignación eliminada correctamente']);
  }

  public function downloadAssignmentPdf($id)
  {
    $assignment = PhoneLineWorker::with(['worker.position', 'worker.area', 'worker.sede.company', 'phoneLine.telephoneAccount', 'phoneLine.telephonePlan'])
      ->findOrFail($id);

    $filename = "acta-asignacion-linea_{$assignment->id}_{$assignment->phone_line_id}.pdf";

    return Pdf::loadView('exports.phone-line-assignment', compact('assignment'))
      ->download($filename);
  }

  public function downloadUnassignmentPdf($id)
  {
    $assignment = PhoneLineWorker::with(['worker.position', 'worker.area', 'worker.sede.company', 'phoneLine.telephoneAccount', 'phoneLine.telephonePlan'])
      ->findOrFail($id);

    $filename = "acta-desasignacion-linea_{$assignment->id}_{$assignment->phone_line_id}.pdf";

    return Pdf::loadView('exports.phone-line-unassignment', compact('assignment'))
      ->download($filename);
  }
}
