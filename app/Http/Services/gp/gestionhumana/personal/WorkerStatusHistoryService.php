<?php

namespace App\Http\Services\gp\gestionhumana\personal;

use App\Http\Resources\gp\gestionhumana\personal\WorkerStatusHistoryResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\personal\WorkerStatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerStatusHistoryService extends BaseService
{
  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      WorkerStatusHistory::query()->with(['employee', 'sede']),
      $request,
      WorkerStatusHistory::filters,
      WorkerStatusHistory::sorts,
      WorkerStatusHistoryResource::class,
    );
  }

  public function show(int $id): WorkerStatusHistoryResource
  {
    $history = WorkerStatusHistory::with(['employee', 'sede', 'writeUser'])->findOrFail($id);

    return new WorkerStatusHistoryResource($history);
  }

  /**
   * Registra un cambio de estado del trabajador (activacion o cese).
   * NOTA: no dispara todavia la automatizacion de LBS/entrega de equipos al cese
   * (planeada para Fase 4 del plan de implementacion de planillas) - solo deja el registro.
   */
  public function store(array $data, ?int $userId): WorkerStatusHistoryResource
  {
    $data['write_id'] = $userId;
    $history = WorkerStatusHistory::create($data);
    $history->load(['employee', 'sede']);

    return new WorkerStatusHistoryResource($history);
  }

  public function currentStatus(int $workerId): ?WorkerStatusHistoryResource
  {
    $history = WorkerStatusHistory::where('empleado_id', $workerId)
      ->orderByDesc('fecha')
      ->orderByDesc('id')
      ->first();

    return $history ? new WorkerStatusHistoryResource($history) : null;
  }
}
