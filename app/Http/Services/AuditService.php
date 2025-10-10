<?php

namespace App\Http\Services;

use App\Models\AuditLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class AuditService
{
  /**
   * Obtener logs de auditoría con filtros
   */
  public function getLogs(array $filters = [], int $perPage = 15): LengthAwarePaginator
  {
    $query = AuditLogs::with(['user'])
      ->latest('created_at');

    // Filtro por usuario
    if (!empty($filters['user_id'])) {
      $query->byUser($filters['user_id']);
    }

    // Filtro por acción
    if (!empty($filters['action'])) {
      $query->action($filters['action']);
    }

    // Filtro por modelo
    if (!empty($filters['model'])) {
      $query->forModel($filters['model']);
    }

    // Filtro por rango de fechas
    if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
      $query->dateRange(
        Carbon::parse($filters['date_from'])->startOfDay(),
        Carbon::parse($filters['date_to'])->endOfDay()
      );
    }

    // Filtro por IP
    if (!empty($filters['ip_address'])) {
      $query->where('ip_address', $filters['ip_address']);
    }

    return $query->paginate($perPage);
  }

  /**
   * Obtener logs de un modelo específico
   */
  public function getModelLogs(Model $model, int $perPage = 15): LengthAwarePaginator
  {
    return $model->auditLogs()
      ->with(['user'])
      ->paginate($perPage);
  }

  /**
   * Obtener logs de un usuario específico
   */
  public function getUserLogs(int $userId, int $perPage = 15): LengthAwarePaginator
  {
    return AuditLogs::byUser($userId)
      ->with(['user'])
      ->latest('created_at')
      ->paginate($perPage);
  }

  /**
   * Obtener estadísticas de auditoría
   */
  public function getStats(array $filters = []): array
  {
    $query = AuditLogs::query();

    // Aplicar filtros de fecha si existen
    if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
      $query->dateRange(
        Carbon::parse($filters['date_from'])->startOfDay(),
        Carbon::parse($filters['date_to'])->endOfDay()
      );
    } else {
      // Por defecto, últimos 30 días
      $query->where('created_at', '>=', Carbon::now()->subDays(30));
    }

    return [
      'total_actions' => $query->count(),
      'actions_by_type' => $query->groupBy('action')
        ->selectRaw('action, count(*) as count')
        ->pluck('count', 'action')
        ->toArray(),
      'most_active_users' => $query->whereNotNull('user_id')
        ->groupBy('user_id', 'user_name')
        ->selectRaw('user_id, user_name, count(*) as count')
        ->orderByDesc('count')
        ->limit(10)
        ->get()
        ->toArray(),
      'actions_by_day' => $query->selectRaw('DATE(created_at) as date, count(*) as count')
        ->groupBy('date')
        ->orderBy('date')
        ->get()
        ->pluck('count', 'date')
        ->toArray(),
    ];
  }

  /**
   * Limpiar logs antiguos
   */
  public function cleanOldLogs(int $daysToKeep = 365): int
  {
    $cutoffDate = Carbon::now()->subDays($daysToKeep);

    return AuditLogs::where('created_at', '<', $cutoffDate)->delete();
  }

  /**
   * Crear log de auditoría manualmente
   */
  public function log(
    Model  $model,
    string $action,
    string $description = null,
    array  $metadata = []
  ): AuditLogs
  {
    return $model->audit($action, $description, $metadata);
  }

  /**
   * Exportar logs a CSV
   */
  public function exportToCsv(array $filters = []): string
  {
    $logs = $this->getLogs($filters, 1000); // Máximo 1000 registros

    $filename = 'audit_logs_' . date('Y-m-d_H-i-s') . '.csv';
    $filepath = storage_path('app/exports/' . $filename);

    // Crear directorio si no existe
    if (!is_dir(dirname($filepath))) {
      mkdir(dirname($filepath), 0755, true);
    }

    $file = fopen($filepath, 'w');

    // Headers CSV
    fputcsv($file, [
      'ID',
      'Usuario',
      'Email',
      'Modelo',
      'ID Registro',
      'Acción',
      'Descripción',
      'IP',
      'Fecha'
    ]);

    // Datos
    foreach ($logs as $log) {
      fputcsv($file, [
        $log->id,
        $log->user_name,
        $log->user_email,
        $log->model_name,
        $log->auditable_id,
        $log->action,
        $log->description,
        $log->ip_address,
        $log->created_at->format('Y-m-d H:i:s')
      ]);
    }

    fclose($file);

    return $filepath;
  }
}
