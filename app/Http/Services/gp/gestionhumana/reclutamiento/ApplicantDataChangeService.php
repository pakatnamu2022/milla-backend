<?php

namespace App\Http\Services\gp\gestionhumana\reclutamiento;

use App\Http\Resources\gp\gestionhumana\reclutamiento\ApplicantDataChangeResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\reclutamiento\Applicant;
use App\Models\gp\gestionhumana\reclutamiento\ApplicantDataChange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Cola de aprobacion de cambios de ficha del postulante (rrhh_temp_data_persona).
 * Equivale a `AdministracionPostulanteController::aprobar/rechazar` del legacy.
 */
class ApplicantDataChangeService extends BaseService
{
  public function __construct(private ApplicantService $applicantService) {}

  public function list(Request $request): JsonResponse
  {
    $request->query->set('status_id', $request->query('status_id', ApplicantDataChange::STATUS_PENDING));

    return $this->getFilteredResults(
      ApplicantDataChange::query()->with('applicant'),
      $request,
      ApplicantDataChange::filters,
      ApplicantDataChange::sorts,
      ApplicantDataChangeResource::class,
    );
  }

  public function show(int $id): ApplicantDataChangeResource
  {
    return new ApplicantDataChangeResource(
      ApplicantDataChange::with('applicant')->findOrFail($id)
    );
  }

  /**
   * Aprueba el cambio de ficha: fusiona sobre `rrhh_persona` todos los campos
   * no vacios del temp (mismo criterio que el legacy) y marca status 19.
   */
  public function approve(int $id): ApplicantDataChangeResource
  {
    return DB::transaction(function () use ($id) {
      $change = ApplicantDataChange::findOrFail($id);

      $applicant = Applicant::withoutGlobalScopes()->findOrFail($change->empleado_id);

      $applicantFillable = $applicant->getFillable();
      $updates = [];

      foreach ($change->getFillable() as $field) {
        if (in_array($field, ['empleado_id', 'status_id', 'obs_rechazado'], true)) {
          continue;
        }

        $value = $change->getAttribute($field);
        if ($value === null || $value === '') {
          continue;
        }

        $target = ApplicantDataChange::APPLICANT_COLUMN_MAP[$field] ?? $field;
        if (!in_array($target, $applicantFillable, true)) {
          continue;
        }

        $updates[$target] = $value;
      }

      if (isset($updates['cv_actualizado']) && $applicant->cv_actualizado && $applicant->cv_actualizado !== $updates['cv_actualizado']) {
        Storage::disk('private')->delete($applicant->cv_actualizado);
      }
      if (isset($updates['foto_adjunto']) && $applicant->foto_adjunto && $applicant->foto_adjunto !== $updates['foto_adjunto']) {
        Storage::disk('private')->delete($applicant->foto_adjunto);
      }

      if (!empty($updates)) {
        $applicant->update($updates);
      }

      $change->update(['status_id' => ApplicantDataChange::STATUS_APPROVED]);

      $this->applicantService->logChange($applicant);

      return $this->show($change->id);
    });
  }

  public function reject(int $id, string $motivo): ApplicantDataChangeResource
  {
    $change = ApplicantDataChange::findOrFail($id);
    $change->update([
      'status_id'     => ApplicantDataChange::STATUS_REJECTED,
      'obs_rechazado' => $motivo,
    ]);

    return $this->show($change->id);
  }
}
