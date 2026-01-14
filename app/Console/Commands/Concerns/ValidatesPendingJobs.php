<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Facades\DB;

trait ValidatesPendingJobs
{
  /**
   * Número máximo de jobs pendientes permitidos del mismo tipo
   */
  protected int $maxPendingJobs = 10;

  /**
   * Verifica si hay espacio para despachar más jobs del tipo especificado
   *
   * @param string $jobClass Clase completa del job (ej: App\Jobs\SyncInvoiceDynamicsJob)
   * @return bool True si se pueden despachar más jobs, False si se alcanzó el límite
   */
  protected function canDispatchMoreJobs(string $jobClass): bool
  {
    $pendingCount = $this->getPendingJobsCount($jobClass);

    if ($pendingCount >= $this->maxPendingJobs) {
      $this->warn("⚠ Límite alcanzado: Ya hay {$pendingCount} jobs de tipo {$this->getJobShortName($jobClass)} pendientes en la cola.");
      $this->warn("No se despacharán más jobs hasta que se procesen los existentes.");
      return false;
    }

    return true;
  }

  /**
   * Obtiene la cantidad de jobs pendientes de un tipo específico
   *
   * @param string $jobClass Clase completa del job
   * @return int Cantidad de jobs pendientes
   */
  protected function getPendingJobsCount(string $jobClass): int
  {
    // Contar jobs en la tabla 'jobs' que contengan la clase especificada
    return DB::table('jobs')
      ->where('payload', 'like', '%' . addslashes($jobClass) . '%')
      ->count();
  }

  /**
   * Obtiene el nombre corto del job (sin namespace)
   *
   * @param string $jobClass Clase completa del job
   * @return string Nombre corto del job
   */
  protected function getJobShortName(string $jobClass): string
  {
    $parts = explode('\\', $jobClass);
    return end($parts);
  }

  /**
   * Muestra información sobre los jobs pendientes
   *
   * @param string $jobClass Clase completa del job
   * @return void
   */
  protected function showPendingJobsInfo(string $jobClass): void
  {
    $pendingCount = $this->getPendingJobsCount($jobClass);

    if ($pendingCount > 0) {
      $this->info("ℹ Actualmente hay {$pendingCount} jobs de tipo {$this->getJobShortName($jobClass)} pendientes en la cola.");
    }
  }
}
