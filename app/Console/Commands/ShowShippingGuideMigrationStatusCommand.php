<?php

namespace App\Console\Commands;

use App\Models\ap\comercial\ShippingGuides;
use Illuminate\Console\Command;

class ShowShippingGuideMigrationStatusCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'shipping-guide:migration-status {--id= : ID de la guía de remisión específica}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Muestra el estado de migración de las guías de remisión';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $shippingGuideId = $this->option('id');

    if ($shippingGuideId) {
      return $this->showSingleGuideStatus($shippingGuideId);
    }

    return $this->showAllGuidesStatus();
  }

  /**
   * Muestra el estado de una guía específica
   */
  protected function showSingleGuideStatus(int $shippingGuideId): int
  {
    $shippingGuide = ShippingGuides::with(['migrationLogs'])->find($shippingGuideId);

    if (!$shippingGuide) {
      $this->error("Guía de remisión no encontrada: {$shippingGuideId}");
      return 1;
    }

    $this->info("=== Guía de Remisión: {$shippingGuide->document_number} ===");
    $this->info("Estado de migración: {$shippingGuide->migration_status}");
    if ($shippingGuide->migrated_at) {
      $this->info("Migrado el: {$shippingGuide->migrated_at->format('Y-m-d H:i:s')}");
    }

    $this->newLine();
    $this->info("=== Detalle de pasos de migración ===");

    if ($shippingGuide->migrationLogs->isEmpty()) {
      $this->warn("No hay logs de migración para esta guía.");
      return 0;
    }

    $headers = ['Paso', 'Estado', 'Proceso Estado', 'Intentos', 'Último Intento', 'Error'];
    $rows = [];

    foreach ($shippingGuide->migrationLogs as $log) {
      $rows[] = [
        $log->step,
        $log->status,
        $log->proceso_estado ?? 'N/A',
        $log->attempts,
        $log->last_attempt_at ? $log->last_attempt_at->format('Y-m-d H:i:s') : 'N/A',
        $log->error_message ? substr($log->error_message, 0, 50) . '...' : '',
      ];
    }

    $this->table($headers, $rows);

    return 0;
  }

  /**
   * Muestra el resumen de todas las guías
   */
  protected function showAllGuidesStatus(): int
  {
    $this->info("=== Resumen de Migración de Guías de Remisión ===");

    $statuses = [
      'pending' => 'Pendientes',
      'in_progress' => 'En Progreso',
      'completed' => 'Completadas',
      'failed' => 'Fallidas',
    ];

    foreach ($statuses as $status => $label) {
      $count = ShippingGuides::where('migration_status', $status)->count();
      $this->info("{$label}: {$count}");
    }

    $this->newLine();

    // Mostrar guías pendientes o con problemas
    $pendingGuides = ShippingGuides::whereIn('migration_status', [
      'pending',
      'in_progress',
      'failed'
    ])->limit(20)->get();

    if ($pendingGuides->isNotEmpty()) {
      $this->info("=== Últimas 20 guías pendientes/fallidas ===");

      $headers = ['ID', 'Número Guía', 'Estado', 'Creada', 'Último Intento'];
      $rows = [];

      foreach ($pendingGuides as $guide) {
        $lastLog = $guide->migrationLogs()
          ->whereNotNull('last_attempt_at')
          ->orderBy('last_attempt_at', 'desc')
          ->first();

        $rows[] = [
          $guide->id,
          $guide->document_number,
          $guide->migration_status,
          $guide->created_at->format('Y-m-d H:i'),
          $lastLog?->last_attempt_at?->format('Y-m-d H:i') ?? 'N/A',
        ];
      }

      $this->table($headers, $rows);
    }

    $this->newLine();
    $this->info("Usa --id=<ID> para ver el detalle de una guía específica.");

    return 0;
  }
}