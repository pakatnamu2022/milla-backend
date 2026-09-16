<?php

namespace App\Http\Controllers\gp\gestionhumana\reclutamiento;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\reclutamiento\UpdateApplicantStatusMessageRequest;
use App\Models\gp\gestionhumana\reclutamiento\ApplicantStatusMessageTemplate;
use Illuminate\Http\JsonResponse;

/**
 * Mensajes automáticos parametrizables por estado de postulante
 * (rrhh_mensaje_estado), editables por Gestión Humana. Uno por cada valor de
 * `Applicant::TIPO_*` (se crea perezosamente al primer `update`).
 */
class ApplicantStatusMessageController extends Controller
{
  public function index(): JsonResponse
  {
    try {
      $templates = ApplicantStatusMessageTemplate::all()->keyBy('tipo_trabajador_id');

      $result = collect(\App\Models\gp\gestionhumana\reclutamiento\Applicant::TIPO_LABELS)
        ->map(fn($label, $tipo) => [
          'tipo_trabajador_id' => $tipo,
          'label'              => $label,
          'asunto'             => $templates[$tipo]->asunto ?? null,
          'contenido'          => $templates[$tipo]->contenido ?? null,
          'activo'             => $templates[$tipo]->activo ?? false,
        ])
        ->values();

      return $this->success($result);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function update(UpdateApplicantStatusMessageRequest $request, int $tipoTrabajadorId): JsonResponse
  {
    try {
      $template = ApplicantStatusMessageTemplate::updateOrCreate(
        ['tipo_trabajador_id' => $tipoTrabajadorId],
        [...$request->validated(), 'activo' => $request->boolean('activo', true)],
      );

      return $this->success($template);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
