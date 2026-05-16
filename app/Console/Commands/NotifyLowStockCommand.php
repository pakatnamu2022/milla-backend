<?php

namespace App\Console\Commands;

use App\Http\Services\common\LowStockNotificationService;
use Illuminate\Console\Command;

class NotifyLowStockCommand extends Command
{
  protected $signature = 'warehouse:notify-low-stock
                          {--stats : Mostrar solo estadísticas sin enviar notificaciones}
                          {--dry-run : Simular qué se enviaría sin enviar notificaciones}';

  protected $description = 'Notifica a los encargados de almacén sobre productos con stock bajo';

  private LowStockNotificationService $notificationService;

  public function __construct(LowStockNotificationService $notificationService)
  {
    parent::__construct();
    $this->notificationService = $notificationService;
  }

  public function handle(): int
  {
    $this->info('🚀 Iniciando proceso de notificaciones de stock bajo...');

    // Si solo se solicitan estadísticas
    if ($this->option('stats')) {
      $this->showStats();
      return 0;
    }

    // Modo dry-run
    if ($this->option('dry-run')) {
      $this->warn('⚠️  MODO DRY-RUN: No se enviarán notificaciones reales');
      $this->showStats();
      $this->line('');
      $this->info('Las notificaciones que se enviarían están listadas arriba.');
      return 0;
    }

    // Ejecutar el proceso de notificaciones
    $this->line('');
    $this->info('📧 Enviando notificaciones...');

    $results = $this->notificationService->notifyLowStock();

    if ($results['success']) {
      $this->line('');
      $this->info("✅ {$results['message']}");

      if ($results['total_notifications'] > 0) {
        $this->displayResults($results['results']);
      } else {
        $this->line('');
        $this->comment('ℹ️  No hay productos con stock bajo en este momento.');
      }
    } else {
      $this->error("❌ Error: {$results['message']}");
      if (isset($results['error'])) {
        $this->error("   Detalle: {$results['error']}");
      }
      return 1;
    }

    $this->line('');
    $this->info('🎉 Proceso completado exitosamente');

    return 0;
  }

  /**
   * Mostrar estadísticas de stock bajo
   */
  private function showStats(): void
  {
    $this->line('');
    $this->info('📊 Estadísticas de Stock Bajo:');

    $stats = $this->notificationService->getLowStockStats();

    if ($stats['total_products'] === 0) {
      $this->line('');
      $this->comment('ℹ️  No hay productos con stock bajo en este momento.');
      return;
    }

    $this->line('');
    $this->line("   📦 Total de productos con stock bajo: <fg=yellow>{$stats['total_products']}</>");
    $this->line("   🏢 Total de sedes afectadas: <fg=yellow>{$stats['total_sedes']}</>");

    $this->line('');
    $this->info('📋 Detalle por Sede:');

    $headers = ['Sede', 'Total Productos', 'Almacenes'];
    $rows = [];

    foreach ($stats['details'] as $detail) {
      $warehousesList = collect($detail['warehouses'])
        ->map(fn($wh) => "{$wh['warehouse_name']} ({$wh['products_count']})")
        ->implode("\n");

      $rows[] = [
        $detail['sede_name'],
        $detail['products_count'],
        $warehousesList
      ];
    }

    $this->table($headers, $rows);
  }

  /**
   * Mostrar resultados de las notificaciones enviadas
   *
   * @param array $results
   */
  private function displayResults(array $results): void
  {
    $this->line('');
    $this->info('📋 Detalle de notificaciones enviadas:');

    $headers = ['Sede', 'Almacén', 'Productos', 'Usuarios Notificados', 'Estado'];
    $rows = [];

    foreach ($results as $result) {
      $rows[] = [
        $result['sede_name'],
        $result['warehouse_name'],
        $result['products_count'],
        $result['users_notified'],
        '✅ Enviado'
      ];
    }

    $this->table($headers, $rows);
  }
}