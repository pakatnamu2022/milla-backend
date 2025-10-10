<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuditLogsResource;
use App\Http\Services\AuditService;
use Illuminate\Http\Request;

class AuditLogsController extends Controller
{
  protected AuditService $service;

  public function __construct(AuditService $service)
  {
    $this->service = $service;
  }

  /**
   * Obtener listado de logs de auditoría con filtros
   */
  public function index(Request $request)
  {
    try {
      $filters = $request->only([
        'user_id',
        'action',
        'model',
        'date_from',
        'date_to',
        'ip_address'
      ]);

      $perPage = $request->input('per_page', 15);

      $logs = $this->service->getLogs($filters, $perPage);

      return AuditLogsResource::collection($logs);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener logs de un usuario específico
   */
  public function userLogs(Request $request, int $userId)
  {
    try {
      $perPage = $request->input('per_page', 15);
      $logs = $this->service->getUserLogs($userId, $perPage);

      return AuditLogsResource::collection($logs);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener logs de un modelo específico
   */
  public function modelLogs(Request $request, string $model, int $id)
  {
    try {
      // Construir el nombre completo de la clase del modelo
      $modelClass = "App\\Models\\" . str_replace('.', '\\', $model);

      if (!class_exists($modelClass)) {
        return $this->error('Modelo no encontrado', 404);
      }

      $modelInstance = $modelClass::findOrFail($id);
      $perPage = $request->input('per_page', 15);

      $logs = $this->service->getModelLogs($modelInstance, $perPage);

      return AuditLogsResource::collection($logs);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener estadísticas de auditoría
   */
  public function stats(Request $request)
  {
    try {
      $filters = $request->only(['date_from', 'date_to']);
      $stats = $this->service->getStats($filters);

      return $this->success($stats);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Exportar logs a CSV
   */
  public function export(Request $request)
  {
    try {
      $filters = $request->only([
        'user_id',
        'action',
        'model',
        'date_from',
        'date_to',
        'ip_address'
      ]);

      $filepath = $this->service->exportToCsv($filters);

      return response()->download($filepath)->deleteFileAfterSend();
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Limpiar logs antiguos (solo para admins)
   */
  public function clean(Request $request)
  {
    try {
      $daysToKeep = $request->input('days', 365);
      $deleted = $this->service->cleanOldLogs($daysToKeep);

      return $this->success([
        'message' => 'Logs eliminados correctamente',
        'deleted_count' => $deleted
      ]);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
