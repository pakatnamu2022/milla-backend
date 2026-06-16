<?php

namespace App\Http\Services\gp\gestionhumana\personal;

use App\Http\Resources\gp\gestionhumana\personal\VacationResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\personal\Vacation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VacationService extends BaseService
{
  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      Vacation::query()->with(['employee', 'sede', 'status']),
      $request,
      Vacation::filters,
      Vacation::sorts,
      VacationResource::class,
    );
  }

  public function show(int $id): VacationResource
  {
    $vacation = Vacation::with(['employee', 'sede', 'status', 'jefaturaUser', 'rrhhUser'])
      ->findOrFail($id);

    return new VacationResource($vacation);
  }

  public function store(array $data): VacationResource
  {
    $vacation = Vacation::create($data);
    $vacation->load(['employee', 'sede', 'status']);

    return new VacationResource($vacation);
  }

  public function update(array $data, int $id): VacationResource
  {
    $vacation = Vacation::findOrFail($id);
    $vacation->update($data);
    $vacation->load(['employee', 'sede', 'status']);

    return new VacationResource($vacation);
  }

  public function destroy(int $id): array
  {
    $vacation = Vacation::findOrFail($id);
    $vacation->update(['status_deleted' => 0]);

    return ['message' => 'Vacación eliminada correctamente.'];
  }

  public function approveJefatura(int $id, int $userId): VacationResource
  {
    $vacation = Vacation::findOrFail($id);
    $vacation->update([
      'aprobacion_jefatura'       => 1,
      'fecha_aprobacion_jefatura' => now(),
      'user_jefatura_id'          => $userId,
    ]);
    $vacation->load(['employee', 'sede', 'status']);

    return new VacationResource($vacation);
  }

  public function approveRrhh(int $id, int $userId): VacationResource
  {
    $vacation = Vacation::findOrFail($id);
    $vacation->update([
      'aprobacion_rrhh'       => 1,
      'fecha_aprobacion_rrhh' => now(),
      'user_id_rrhh'          => $userId,
    ]);
    $vacation->load(['employee', 'sede', 'status']);

    return new VacationResource($vacation);
  }
}
