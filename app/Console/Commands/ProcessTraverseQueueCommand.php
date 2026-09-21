<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ProcessTraverseQueueCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'traverse:process-queue
                          {--tries=3 : Número de intentos antes de fallar}
                          {--timeout=300 : Timeout en segundos}
                          {--sleep=3 : Segundos de espera cuando la cola está vacía}
                          {--daemon : Ejecutar en modo daemon (continuo)}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Procesa la cola de trabajos de migración de travesía a Dynamics';

  /**
   * Execute the console command.
   */
  public function handle(): int
  {
    $tries = $this->option('tries');
    $timeout = $this->option('timeout');
    $sleep = $this->option('sleep');
    $daemon = $this->option('daemon');

    $this->info('Iniciando procesamiento de cola traverse_migration...');
    $this->info("Configuración: tries={$tries}, timeout={$timeout}s, sleep={$sleep}s");

    if ($daemon) {
      $this->info('Modo daemon: El worker se ejecutará indefinidamente');
      $this->warn('Presiona Ctrl+C para detener');
    }

    // Construir comando de queue:work
    $command = [
      'queue:work',
      '--queue' => 'traverse_migration',
      '--tries' => $tries,
      '--timeout' => $timeout,
      '--sleep' => $sleep,
    ];

    // Si es daemon, agregar --daemon y --max-time
    if ($daemon) {
      $command['--daemon'] = true;
      // Reiniciar el worker cada 8 horas para liberar memoria
      $command['--max-time'] = 28800;
    } else {
      // En modo no-daemon, procesar solo los trabajos disponibles
      $command['--stop-when-empty'] = true;
    }

    // Ejecutar queue:work
    $exitCode = Artisan::call('queue:work', $command);

    if ($exitCode === 0) {
      $this->info('Procesamiento de cola completado exitosamente');
    } else {
      $this->error("El worker terminó con código de salida: {$exitCode}");
    }

    return $exitCode;
  }
}