<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class JobStatusController extends Controller
{
  /**
   * Límite máximo de jobs pendientes por tipo
   */
  private const MAX_PENDING_JOBS = 10;

  /**
   * Mapeo de clases de jobs a nombres legibles y sus colas
   */
  private const JOB_NAMES = [
    'App\\Jobs\\SyncInvoiceDynamicsJob' => 'Sincronización de Facturas Dynamics',
    'App\\Jobs\\SyncCreditNoteDynamicsJob' => 'Sincronización de Notas de Crédito Dynamics',
    'App\\Jobs\\SyncSalesDocumentJob' => 'Sincronización de Documentos Electrónicos',
    'App\\Jobs\\VerifyAndMigratePurchaseOrderJob' => 'Verificación y Migración de Órdenes de Compra',
    'App\\Jobs\\VerifyAndMigrateShippingGuideJob' => 'Verificación y Migración de Guías de Remisión',
  ];

  /**
   * Mapeo de clases de jobs a sus colas correspondientes
   */
  private const JOB_QUEUES = [
    'App\\Jobs\\SyncInvoiceDynamicsJob' => 'invoice_sync',
    'App\\Jobs\\SyncCreditNoteDynamicsJob' => 'credit_note_sync',
    'App\\Jobs\\SyncSalesDocumentJob' => 'electronic_documents',
    'App\\Jobs\\VerifyAndMigratePurchaseOrderJob' => 'purchase_orders',
    'App\\Jobs\\VerifyAndMigrateShippingGuideJob' => 'shipping_guides',
  ];

  /**
   * Obtiene el estado general de los jobs en la cola
   */
  public function index(): JsonResponse
  {
    $jobsByType = $this->getJobsByType();
    $totalPending = $this->getTotalPendingJobs();
    $failedJobs = $this->getFailedJobsCount();

    return response()->json([
      'success' => true,
      'data' => [
        'summary' => [
          'total_pending' => $totalPending,
          'total_failed' => $failedJobs,
          'max_allowed_per_type' => self::MAX_PENDING_JOBS,
        ],
        'jobs_by_type' => $jobsByType,
        'timestamp' => now()->toDateTimeString(),
      ],
    ]);
  }

  /**
   * Obtiene el estado de un tipo de job específico
   */
  public function show(string $jobType): JsonResponse
  {
    $jobClass = $this->resolveJobClass($jobType);

    if (!$jobClass) {
      return response()->json([
        'success' => false,
        'message' => 'Tipo de job no válido',
        'available_types' => array_keys(self::JOB_NAMES),
      ], 404);
    }

    $pendingCount = $this->getPendingJobsCount($jobClass);
    $jobs = $this->getJobDetails($jobClass);

    return response()->json([
      'success' => true,
      'data' => [
        'job_type' => $jobType,
        'job_name' => self::JOB_NAMES[$jobClass] ?? 'Desconocido',
        'pending_count' => $pendingCount,
        'max_allowed' => self::MAX_PENDING_JOBS,
        'available_slots' => max(0, self::MAX_PENDING_JOBS - $pendingCount),
        'is_at_limit' => $pendingCount >= self::MAX_PENDING_JOBS,
        'jobs' => $jobs,
      ],
    ]);
  }

  /**
   * Obtiene jobs agrupados por tipo
   */
  private function getJobsByType(): array
  {
    $result = [];

    foreach (self::JOB_NAMES as $class => $name) {
      $pendingCount = $this->getPendingJobsCount($class);

      $result[] = [
        'type' => $this->getJobShortName($class),
        'name' => $name,
        'class' => $class,
        'pending_count' => $pendingCount,
        'max_allowed' => self::MAX_PENDING_JOBS,
        'available_slots' => max(0, self::MAX_PENDING_JOBS - $pendingCount),
        'is_at_limit' => $pendingCount >= self::MAX_PENDING_JOBS,
        'status' => $this->getJobTypeStatus($pendingCount),
      ];
    }

    return $result;
  }

  /**
   * Obtiene la cantidad de jobs pendientes de un tipo específico
   */
  private function getPendingJobsCount(string $jobClass): int
  {
    // Obtener el nombre de la cola para este job
    $queueName = self::JOB_QUEUES[$jobClass] ?? null;

    // Si el job tiene una cola específica, contar por cola (más eficiente)
    if ($queueName) {
      return Job::inQueue($queueName)->count();
    }

    // Fallback: contar por clase si no se encuentra la cola
    return Job::ofClass($jobClass)->count();
  }

  /**
   * Obtiene el total de jobs pendientes
   */
  private function getTotalPendingJobs(): int
  {
    return Job::count();
  }

  /**
   * Obtiene el total de jobs fallidos
   */
  private function getFailedJobsCount(): int
  {
    return DB::table('failed_jobs')->count();
  }

  /**
   * Obtiene detalles de los jobs de un tipo específico
   */
  private function getJobDetails(string $jobClass): array
  {
    // Obtener el nombre de la cola para este job
    $queueName = self::JOB_QUEUES[$jobClass] ?? null;

    // Si el job tiene una cola específica, filtrar por cola (más eficiente)
    if ($queueName) {
      $jobs = Job::inQueue($queueName)->oldestFirst()->limit(20)->get();
    } else {
      // Fallback: filtrar por clase
      $jobs = Job::ofClass($jobClass)->oldestFirst()->limit(20)->get();
    }

    return $jobs->map(function (Job $job) {
      return [
        'id' => $job->id,
        'queue' => $job->queue,
        'attempts' => $job->attempts,
        'created_at' => $job->created_at_formatted,
        'available_at' => $job->available_at_formatted,
        'reserved_at' => $job->reserved_at_formatted,
      ];
    })->toArray();
  }

  /**
   * Resuelve el nombre corto del tipo de job a su clase completa
   */
  private function resolveJobClass(string $jobType): ?string
  {
    foreach (self::JOB_NAMES as $class => $name) {
      if ($this->getJobShortName($class) === $jobType) {
        return $class;
      }
    }

    return null;
  }

  /**
   * Obtiene el nombre corto del job (sin namespace)
   */
  private function getJobShortName(string $jobClass): string
  {
    $parts = explode('\\', $jobClass);
    return end($parts);
  }

  /**
   * Obtiene el estado del tipo de job según la cantidad pendiente
   */
  private function getJobTypeStatus(int $pendingCount): string
  {
    if ($pendingCount >= self::MAX_PENDING_JOBS) {
      return 'at_limit';
    }

    if ($pendingCount >= self::MAX_PENDING_JOBS * 0.7) {
      return 'warning';
    }

    return 'ok';
  }
}
