<?php

namespace App\Http\Services\gp\gestionhumana\reclutamiento;

use App\Http\Resources\gp\gestionhumana\reclutamiento\RecruitmentProcessHistoryResource;
use App\Http\Resources\gp\gestionhumana\reclutamiento\RecruitmentProcessResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\EmailService;
use App\Http\Services\common\ExportService;
use App\Models\gp\gestionsistema\Area;
use App\Models\gp\gestionsistema\Position;
use App\Models\gp\gestionhumana\reclutamiento\Applicant;
use App\Models\gp\gestionhumana\reclutamiento\ProcessStageMessageTemplate;
use App\Models\gp\gestionhumana\reclutamiento\RecruitmentProcess;
use App\Models\gp\gestionhumana\reclutamiento\RecruitmentProcessHistory;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecruitmentProcessService extends BaseService
{
  private const RELATIONS = ['sede', 'area', 'position', 'status', 'requester'];

  public function __construct(private ExportService $exportService)
  {
  }

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
        (int)$data['cargo_id'],
        (int)$data['area_id'],
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

      $this->notifyStage($process, ProcessStageMessageTemplate::ETAPA_CREADO);

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

      $cargoId = (int)($data['cargo_id'] ?? $process->cargo_id);
      $areaId = (int)($data['area_id'] ?? $process->area_id);
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

    $this->addHistory($process->id, RecruitmentProcessHistory::ACCION_CERRADO);
    $this->notifyStage($process, ProcessStageMessageTemplate::ETAPA_CERRADO);

    return $this->show($process->id);
  }

  /**
   * Reabre un proceso cerrado (ej. el postulante seleccionado se retracta).
   * Revierte a "Postulante" a quien haya quedado como Seleccionado, para que
   * Reclutamiento pueda gestionarlo de nuevo (repostular, rechazar, etc.).
   */
  public function reopen(int $id): RecruitmentProcessResource
  {
    return DB::transaction(function () use ($id) {
      $process = RecruitmentProcess::findOrFail($id);

      if ($process->status_id !== RecruitmentProcess::STATUS_CLOSED) {
        throw new \RuntimeException('El proceso no está cerrado.');
      }

      $process->update([
        'status_id'        => RecruitmentProcess::STATUS_IN_PROCESS,
        'fecha_fin_cierre' => null,
      ]);

      Applicant::where('proceso_postulacion_id', $id)
        ->where('tipo_trabajador_id', Applicant::TIPO_SELECCIONADO)
        ->update([
          'tipo_trabajador_id' => Applicant::TIPO_POSTULANTE,
          'motivo_status'      => null,
        ]);

      $this->addHistory($id, RecruitmentProcessHistory::ACCION_REABIERTO, 'Proceso reabierto.');

      return $this->show($id);
    });
  }

  /**
   * Anulacion logica (status_deleted = 0). Equivale a `delete()` legacy.
   */
  public function destroy(int $id): void
  {
    $process = RecruitmentProcess::findOrFail($id);
    $process->update(['status_deleted' => 0]);
  }

  public function export(Request $request)
  {
    return $this->exportService->exportFromRequest($request, RecruitmentProcess::class);
  }

  /**
   * Pausa el proceso a pedido de la jefatura: el tiempo pausado no se
   * contabiliza en el indicador de cobertura (ver coverage()).
   */
  public function pause(int $id, string $motivo): RecruitmentProcessResource
  {
    return DB::transaction(function () use ($id, $motivo) {
      $process = RecruitmentProcess::findOrFail($id);

      if ($process->pausado) {
        throw new \RuntimeException('El proceso ya está pausado.');
      }
      if ($process->status_id === RecruitmentProcess::STATUS_CLOSED) {
        throw new \RuntimeException('No se puede pausar un proceso cerrado.');
      }

      $process->update([
        'pausado'            => true,
        'motivo_pausa'       => $motivo,
        'fecha_inicio_pausa' => now(),
        'veces_pausado'      => $process->veces_pausado + 1,
      ]);

      $this->addHistory($process->id, RecruitmentProcessHistory::ACCION_PAUSADO, $motivo);
      $this->notifyStage($process, ProcessStageMessageTemplate::ETAPA_PAUSADO, $motivo);

      return $this->show($process->id);
    });
  }

  /**
   * Reanuda un proceso pausado: el conteo de plazo continúa desde donde
   * quedó (se suman al plazo los días hábiles que estuvo pausado).
   */
  public function resume(int $id): RecruitmentProcessResource
  {
    return DB::transaction(function () use ($id) {
      $process = RecruitmentProcess::findOrFail($id);

      if (!$process->pausado) {
        throw new \RuntimeException('El proceso no está pausado.');
      }

      $diasPausa = $this->countBusinessDays(
        CarbonImmutable::parse($process->fecha_inicio_pausa),
        CarbonImmutable::now(),
      );

      $process->update([
        'pausado'            => false,
        'motivo_pausa'       => null,
        'fecha_inicio_pausa' => null,
        'dias_pausados'      => $process->dias_pausados + $diasPausa,
        'fecha_fin_plazo'    => $this->addBusinessDays(
          optional($process->fecha_fin_plazo)->format('Y-m-d') ?? now()->format('Y-m-d'),
          $diasPausa,
        ),
      ]);

      $this->addHistory($process->id, RecruitmentProcessHistory::ACCION_REANUDADO, "{$diasPausa} día(s) hábil(es) de pausa.");

      return $this->show($process->id);
    });
  }

  /**
   * Agrega días adicionales al plazo de uno o varios procesos a la vez
   * (previa coordinación con jefatura). Ignora los procesos ya cerrados.
   *
   * @return RecruitmentProcessResource[]
   */
  public function addDaysToMany(array $processIds, int $dias, string $motivo): array
  {
    return DB::transaction(function () use ($processIds, $dias, $motivo) {
      $updated = [];

      foreach (RecruitmentProcess::whereIn('id', $processIds)->get() as $process) {
        if ($process->status_id === RecruitmentProcess::STATUS_CLOSED) {
          continue;
        }

        $process->update([
          'dias_plazo'      => $process->dias_plazo + $dias,
          'fecha_fin_plazo' => $this->addBusinessDays(
            optional($process->fecha_fin_plazo)->format('Y-m-d') ?? now()->format('Y-m-d'),
            $dias,
          ),
        ]);

        $this->addHistory($process->id, RecruitmentProcessHistory::ACCION_DIAS_ADICIONALES, $motivo, $dias);

        $updated[] = $this->show($process->id);
      }

      return $updated;
    });
  }

  /**
   * Indicador de tiempo de cobertura: días hábiles reales de gestión
   * (excluye los días en que el proceso estuvo pausado), separado del plazo
   * asignado, para diferenciar demora de Reclutamiento vs. demora por pausa
   * de jefatura.
   */
  public function coverage(int $id): array
  {
    $process = RecruitmentProcess::findOrFail($id);

    $hasta = $process->status_id === RecruitmentProcess::STATUS_CLOSED
      ? CarbonImmutable::parse($process->fecha_fin_cierre ?? $process->updated_at)
      : CarbonImmutable::now();

    $diasTranscurridos = $this->countBusinessDays(CarbonImmutable::parse($process->fecha_inicio), $hasta);
    $diasPausaActual = $process->pausado
      ? $this->countBusinessDays(CarbonImmutable::parse($process->fecha_inicio_pausa), CarbonImmutable::now())
      : 0;

    $diasGestion = max(0, $diasTranscurridos - $process->dias_pausados - $diasPausaActual);

    return [
      'dias_plazo'         => $process->dias_plazo,
      'dias_transcurridos' => $diasTranscurridos,
      'dias_pausados'      => $process->dias_pausados + $diasPausaActual,
      'dias_gestion'       => $diasGestion,
      'dentro_de_plazo'    => $diasGestion <= $process->dias_plazo,
      'pausado'            => $process->pausado,
    ];
  }

  public function history(int $id): array
  {
    RecruitmentProcess::findOrFail($id);

    return RecruitmentProcessHistoryResource::collection(
      RecruitmentProcessHistory::with('user')
        ->where('proceso_postulacion_id', $id)
        ->latest()
        ->get()
    )->resolve();
  }

  /**
   * Envía el mensaje automático parametrizable (`rrhh_mensaje_etapa_proceso`)
   * configurado para la etapa del proceso, si existe uno activo y el
   * solicitante (cliente interno / jefatura) tiene email registrado.
   * Editable por Gestión Humana vía ProcessStageMessageTemplate.
   */
  public function notifyStage(RecruitmentProcess $process, string $etapa, ?string $detalle = null): void
  {
    $process->loadMissing(['requester', 'sede', 'area', 'position']);

    if (!$process->requester?->email) {
      return;
    }

    $template = ProcessStageMessageTemplate::where('etapa', $etapa)
      ->where('activo', true)
      ->first();
    if (!$template) {
      return;
    }

    $body = str_replace(
      ['{$proceso}', '{$cargo}', '{$area}', '{$sede}', '{$solicitante}', '{$detalle}'],
      [
        '<strong>' . $process->nombre_postulacion . '</strong>',
        '<strong>' . ($process->position?->name ?? '') . '</strong>',
        '<strong>' . ($process->area?->name ?? '') . '</strong>',
        '<strong>' . ($process->sede?->abreviatura ?? '') . '</strong>',
        '<strong>' . ($process->requester?->name ?? '') . '</strong>',
        $detalle ?? '',
      ],
      $template->contenido,
    );

    (new EmailService())->send([
      'to'       => [$process->requester->email],
      'subject'  => $template->asunto,
      'template' => 'emails.reclutamiento-notification',
      'data'     => [
        'title'     => $template->asunto,
        'body_html' => $body,
      ],
    ]);
  }

  private function addHistory(int $processId, string $accion, ?string $detalle = null, ?int $diasAgregados = null): void
  {
    RecruitmentProcessHistory::create([
      'proceso_postulacion_id' => $processId,
      'accion'                 => $accion,
      'detalle'                => $detalle,
      'dias_agregados'         => $diasAgregados,
      'usuario_id'             => auth()->id(),
    ]);
  }

  /**
   * Cuenta días hábiles (lunes a viernes) entre dos fechas/horas, sin contar
   * el día de inicio.
   */
  private function countBusinessDays(CarbonImmutable $from, CarbonImmutable $to): int
  {
    $cursor = $from->startOfDay();
    $end = $to->startOfDay();
    $count = 0;

    while ($cursor->lt($end)) {
      $cursor = $cursor->addDay();
      if (!$cursor->isWeekend()) {
        $count++;
      }
    }

    return $count;
  }

  /**
   * @return array{0:int,1:string,2:int|null} [dias_plazo, fecha_fin_plazo (Y-m-d), centro_costo_id]
   */
  private function resolveDerivedFields(int $cargoId, int $areaId, string $fechaInicio): array
  {
    $diasPlazo = (int)(Position::query()->whereKey($cargoId)->value('plazo_proceso_seleccion') ?? 0);
    $centroCostoId = Area::query()->whereKey($areaId)->value('centro_costo_id');
    $fechaFinPlazo = $this->addBusinessDays($fechaInicio, $diasPlazo);

    return [$diasPlazo, $fechaFinPlazo, $centroCostoId !== null ? (int)$centroCostoId : null];
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
