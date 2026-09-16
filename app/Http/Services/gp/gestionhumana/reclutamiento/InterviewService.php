<?php

namespace App\Http\Services\gp\gestionhumana\reclutamiento;

use App\Http\Resources\gp\gestionhumana\reclutamiento\InterviewResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\reclutamiento\Interview;
use App\Models\gp\gestionhumana\reclutamiento\ProcessStageMessageTemplate;
use App\Models\gp\gestionhumana\reclutamiento\RecruitmentProcess;
use App\Models\gp\gestionhumana\reclutamiento\RecruitmentProcessCompetence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Etapa de entrevista (RAR): no todo postulante llega aquí. Al crear una
 * entrevista se generan automáticamente las filas de InterviewScore (sin
 * calificar) para cada subcompetencia configurada en el proceso
 * (RecruitmentProcessCompetence), y el promedio se recalcula solo al
 * guardar puntajes (ver InterviewScore::booted).
 */
class InterviewService extends BaseService
{
  private const RELATIONS = ['process', 'applicant', 'interviewer', 'scores.subCompetence.competence'];

  public function __construct(private RecruitmentProcessService $processService) {}

  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      Interview::query()->with(self::RELATIONS),
      $request,
      Interview::filters,
      Interview::sorts,
      InterviewResource::class,
    );
  }

  public function show(int $id): InterviewResource
  {
    return new InterviewResource(Interview::with(self::RELATIONS)->findOrFail($id));
  }

  public function store(array $data): InterviewResource
  {
    return DB::transaction(function () use ($data) {
      $fase = (int) $data['fase'];

      $exists = Interview::where('proceso_postulacion_id', $data['proceso_postulacion_id'])
        ->where('persona_id', $data['persona_id'])
        ->where('fase', $fase)
        ->exists();
      if ($exists) {
        throw new \RuntimeException('Este postulante ya tiene una entrevista de esta fase registrada en este proceso.');
      }

      if ($fase === Interview::FASE_JEFE) {
        $rrhhCalificada = Interview::where('proceso_postulacion_id', $data['proceso_postulacion_id'])
          ->where('persona_id', $data['persona_id'])
          ->where('fase', Interview::FASE_RRHH)
          ->whereNotNull('resultado_promedio')
          ->exists();
        if (!$rrhhCalificada) {
          throw new \RuntimeException('Debe registrarse y calificar la entrevista con RRHH antes de crear la entrevista con el jefe.');
        }
      }

      $interview = Interview::create([
        ...$data,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
      ]);

      $competences = RecruitmentProcessCompetence::where('proceso_postulacion_id', $data['proceso_postulacion_id'])->get();
      foreach ($competences as $competence) {
        $interview->scores()->create(['sub_competencia_id' => $competence->sub_competencia_id]);
      }

      $isFirstInterview = Interview::where('proceso_postulacion_id', $data['proceso_postulacion_id'])->count() === 1;
      if ($isFirstInterview) {
        $process = RecruitmentProcess::find($data['proceso_postulacion_id']);
        if ($process) {
          $this->processService->notifyStage($process, ProcessStageMessageTemplate::ETAPA_ENTREVISTAS);
        }
      }

      return $this->show($interview->id);
    });
  }

  public function update(int $id, array $data): InterviewResource
  {
    $interview = Interview::findOrFail($id);
    $interview->update([...$data, 'updated_by' => auth()->id()]);

    return $this->show($interview->id);
  }

  /**
   * Guarda/actualiza (upsert) las calificaciones por subcompetencia de una
   * entrevista. El promedio (resultado_promedio) se recalcula solo, vía
   * InterviewScore::booted.
   */
  public function score(int $id, array $scores): InterviewResource
  {
    return DB::transaction(function () use ($id, $scores) {
      $interview = Interview::findOrFail($id);

      foreach ($scores as $row) {
        $interview->scores()->updateOrCreate(
          ['sub_competencia_id' => $row['sub_competencia_id']],
          ['puntaje' => $row['puntaje']],
        );
      }

      return $this->show($interview->id);
    });
  }

  public function destroy(int $id): void
  {
    Interview::findOrFail($id)->delete();
  }

  /**
   * Subcompetencias configuradas para la etapa de entrevista de un proceso.
   */
  public function listCompetences(int $processId)
  {
    return RecruitmentProcessCompetence::with('subCompetence.competence')
      ->where('proceso_postulacion_id', $processId)
      ->orderBy('orden')
      ->get();
  }

  /**
   * Reemplaza el set de subcompetencias configuradas para la entrevista de un
   * proceso (máx. 5, según lo que configure el usuario).
   */
  public function syncCompetences(int $processId, array $subCompetences): void
  {
    DB::transaction(function () use ($processId, $subCompetences) {
      RecruitmentProcess::findOrFail($processId);

      RecruitmentProcessCompetence::where('proceso_postulacion_id', $processId)->delete();

      foreach ($subCompetences as $index => $row) {
        RecruitmentProcessCompetence::create([
          'proceso_postulacion_id' => $processId,
          'sub_competencia_id'     => $row['id'],
          'orden'                  => $row['orden'] ?? ($index + 1),
        ]);
      }
    });
  }
}
