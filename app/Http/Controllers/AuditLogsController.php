<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogRequest;
use App\Http\Resources\AuditLogsResource;
use App\Http\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

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
   * Obtener logs del sistema (laravel.log) con filtros
   *
   * Query params:
   *   type         - Nivel del log (DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY)
   *   environment  - Entorno (local, production, etc.)
   *   date_from    - Fecha inicial (Y-m-d)
   *   date_to      - Fecha final (Y-m-d)
   *   search       - Palabra clave en el mensaje
   *   page         - Página actual (default 1)
   *   per_page     - Registros por página (default 50)
   */
  public function logs(LogRequest $request)
  {
    try {
      $logFile = storage_path('logs/laravel.log');

      if (!File::exists($logFile)) {
        return $this->error('No logs found.', 404);
      }

      $lines = explode("\n", File::get($logFile));

      // Parsear todas las líneas válidas del log
      $allLogs = [];
      foreach ($lines as $line) {
        if (preg_match('/^\[(.*?)\] (.*?)\.(.*?): (.*)$/', $line, $matches)) {
          $allLogs[] = [
            'date'        => $matches[1],
            'environment' => $matches[2],
            'type'        => strtoupper($matches[3]),
            'message'     => $matches[4],
          ];
        }
      }

      $allLogs = array_reverse($allLogs);

      // Tipos disponibles antes de filtrar (para que el frontend pueda mostrarlos)
      $availableTypes = array_values(array_unique(array_column($allLogs, 'type')));
      sort($availableTypes);

      // Aplicar filtros
      $type        = $request->input('type');
      $environment = $request->input('environment');
      $dateFrom    = $request->input('date_from');
      $dateTo      = $request->input('date_to');
      $search      = $request->input('search');

      $filteredLogs = array_filter($allLogs, function ($log) use ($type, $environment, $dateFrom, $dateTo, $search) {
        if ($type && $log['type'] !== strtoupper($type)) {
          return false;
        }

        if ($environment && $log['environment'] !== $environment) {
          return false;
        }

        if ($dateFrom && Carbon::parse($log['date']) < Carbon::parse($dateFrom)->startOfDay()) {
          return false;
        }

        if ($dateTo && Carbon::parse($log['date']) > Carbon::parse($dateTo)->endOfDay()) {
          return false;
        }

        if ($search && !str_contains(strtolower($log['message']), strtolower($search))) {
          return false;
        }

        return true;
      });

      $filteredLogs = array_values($filteredLogs);

      // Paginación manual
      $perPage = (int) $request->input('per_page', 50);
      $page    = (int) $request->input('page', 1);
      $total   = count($filteredLogs);

      $paginatedLogs = array_slice($filteredLogs, ($page - 1) * $perPage, $perPage);

      return $this->success([
        'logs'            => $paginatedLogs,
        'available_types' => $availableTypes,
        'meta' => [
          'total'        => $total,
          'per_page'     => $perPage,
          'current_page' => $page,
          'last_page'    => (int) ceil($total / $perPage),
        ],
      ]);
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
