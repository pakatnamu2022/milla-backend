<?php

namespace App\Http\Services\gp\gestionhumana\reclutamiento;

use App\Http\Resources\gp\gestionhumana\reclutamiento\RecruitmentProcessResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\Area;
use App\Models\gp\gestionsistema\Position;
use App\Models\gp\gestionhumana\reclutamiento\RecruitmentProcess;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecruitmentProcessService extends BaseService
{
  private const RELATIONS = ['sede', 'area', 'position', 'status'];

  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      RecruitmentProcess::query()->with(self::RELATIONS)->withCount('applicants'),
      $request,
      RecruitmentProcess::filters,
      RecruitmentProcess::sorts,
      RecruitmentProcessResource::class,
    );
  }

  public function show(int $id): RecruitmentProcessResource
  {
    $process = RecruitmentProcess::with(self::RELATIONS)->withCount('applicants')->findOrFail($id);

    return new RecruitmentProcessResource($process);
  }

  public function store(array $data): RecruitmentProcessResource
  {
    return DB::transaction(function () use ($data) {
      [$diasPlazo, $fechaFinPlazo, $centroCostoId] = $this->resolveDerivedFields(
        (int) $data['cargo_id'],
        (int) $data['area_id'],
        $data['fecha_inicio'],
      );

      $process = RecruitmentProcess::create([
        ...$data,
        'nombre_postulacion' => trim($data['nombre_postulacion']),
        'centro_costo_id'    => $centroCostoId,
        'dias_plazo'         => $diasPlazo,
        'fecha_fin_plazo'    => $fechaFinPlazo,
        'status_id'          => RecruitmentProcess::STATUS_OPEN,
        'status_deleted'     => 1,
      ]);

      return $this->show($process->id);
    });
  }

  public function update(int $id, array $data): RecruitmentProcessResource
  {
    return DB::transaction(function () use ($id, $data) {
      $process = RecruitmentProcess::findOrFail($id);

      if ($process->status_id === RecruitmentProcess::STATUS_CLOSED) {
        throw new \RuntimeException('No se puede editar un proceso cerrado.');
      }

      $cargoId  = (int) ($data['cargo_id'] ?? $process->cargo_id);
      $areaId   = (int) ($data['area_id'] ?? $process->area_id);
      $fechaIni = $data['fecha_inicio'] ?? optional($process->fecha_inicio)->format('Y-m-d');

      [$diasPlazo, $fechaFinPlazo, $centroCostoId] = $this->resolveDerivedFields($cargoId, $areaId, $fechaIni);

      if (isset($data['nombre_postulacion'])) {
        $data['nombre_postulacion'] = trim($data['nombre_postulacion']);
      }

      $process->update([
        ...$data,
        'centro_costo_id' => $centroCostoId,
        'dias_plazo'      => $diasPlazo,
        'fecha_fin_plazo' => $fechaFinPlazo,
      ]);

      return $this->show($process->id);
    });
  }

  /**
   * Cierra el proceso (status 11 + fecha_fin_cierre = hoy). Equivale a `finalizar()` legacy.
   */
  public function close(int $id): RecruitmentProcessResource
  {
    $process = RecruitmentProcess::findOrFail($id);
    $process->update([
      'status_id'        => RecruitmentProcess::STATUS_CLOSED,
      'fecha_fin_cierre' => now()->format('Y-m-d'),
    ]);

    return $this->show($process->id);
  }

  /**
   * Anulacion logica (status_deleted = 0). Equivale a `delete()` legacy.
   */
  public function destroy(int $id): void
  {
    $process = RecruitmentProcess::findOrFail($id);
    $process->update(['status_deleted' => 0]);
  }

  /**
   * @return array{0:int,1:string,2:int|null} [dias_plazo, fecha_fin_plazo (Y-m-d), centro_costo_id]
   */
  private function resolveDerivedFields(int $cargoId, int $areaId, string $fechaInicio): array
  {
    $diasPlazo = (int) (Position::query()->whereKey($cargoId)->value('plazo_proceso_seleccion') ?? 0);
    $centroCostoId = Area::query()->whereKey($areaId)->value('centro_costo_id');
    $fechaFinPlazo = $this->addBusinessDays($fechaInicio, $diasPlazo);

    return [$diasPlazo, $fechaFinPlazo, $centroCostoId !== null ? (int) $centroCostoId : null];
  }

  /**
   * Suma `$dias` dias habiles (lunes a viernes) a una fecha. Reemplaza el
   * `sumasdiasemana()` del legacy con una implementacion exacta.
   */
  private function addBusinessDays(string $date, int $days): string
  {
    $cursor = CarbonImmutable::createFromFormat('Y-m-d', $date)->startOfDay();
    $added = 0;

    while ($added < $days) {
      $cursor = $cursor->addDay();
      if (!$cursor->isWeekend()) {
        $added++;
      }
    }

    return $cursor->format('Y-m-d');
  }
}
