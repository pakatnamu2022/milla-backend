<?php

namespace App\Http\Controllers\gp\gestionhumana\reclutamiento;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\reclutamiento\UpdateProcessStageMessageRequest;
use App\Models\gp\gestionhumana\reclutamiento\ProcessStageMessageTemplate;
use Illuminate\Http\JsonResponse;

/**
 * Mensajes automáticos parametrizables por etapa del proceso
 * (rrhh_mensaje_etapa_proceso), enviados al cliente interno / jefatura que
 * solicitó la posición. Uno por cada `ProcessStageMessageTemplate::ETAPA_*`
 * (se crea perezosamente al primer `update`).
 */
class ProcessStageMessageController extends Controller
{
  public function index(): JsonResponse
  {
    try {
      $templates = ProcessStageMessageTemplate::all()->keyBy('etapa');

      $result = collect(ProcessStageMessageTemplate::ETAPA_LABELS)
        ->map(fn($label, $etapa) => [
          'etapa'     => $etapa,
          'label'     => $label,
          'asunto'    => $templates[$etapa]->asunto ?? null,
          'contenido' => $templates[$etapa]->contenido ?? null,
          'activo'    => $templates[$etapa]->activo ?? false,
        ])
        ->values();

      return $this->success($result);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function update(UpdateProcessStageMessageRequest $request, string $etapa): JsonResponse
  {
    try {
      if (!array_key_exists($etapa, ProcessStageMessageTemplate::ETAPA_LABELS)) {
        return $this->error('Etapa no válida.');
      }

      $template = ProcessStageMessageTemplate::updateOrCreate(
        ['etapa' => $etapa],
        [...$request->validated(), 'activo' => $request->boolean('activo', true)],
      );

      return $this->success($template);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
