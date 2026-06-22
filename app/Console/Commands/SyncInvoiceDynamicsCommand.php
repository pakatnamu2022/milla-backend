<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ValidatesPendingJobs;
use App\Jobs\SyncInvoiceDynamicsJob;
use App\Models\ap\compras\PurchaseOrder;
use Illuminate\Console\Command;

class SyncInvoiceDynamicsCommand extends Command
{
  use ValidatesPendingJobs;

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'po:sync-invoice-dynamics {--id= : ID de la orden de compra específica} {--all : Sincronizar todas las OC sin invoice_dynamics}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Sincroniza el campo invoice_dynamics consultando el PA de Dynamics';

  /**
   * Execute the console command.
   */
  public function handle(): int
  {
    $purchaseOrderId = $this->option('id');
    $all = $this->option('all');

    if (!$purchaseOrderId && !$all) {
      $this->error('Debe especificar --id o --all');
      return Command::FAILURE;
    }

    if ($purchaseOrderId && $all) {
      $this->error('No puede especificar --id y --all al mismo tiempo');
      return Command::FAILURE;
    }

    if ($purchaseOrderId) {
      return $this->syncSinglePurchaseOrder((int)$purchaseOrderId);
    }

    return $this->syncAllPurchaseOrders();
  }

  /**
   * Sincroniza una orden de compra específica
   */
  protected function syncSinglePurchaseOrder(int $purchaseOrderId): int
  {
    $purchaseOrder = PurchaseOrder::find($purchaseOrderId);

    if (!$purchaseOrder) {
      $this->error("Orden de compra #{$purchaseOrderId} no encontrada");
      return Command::FAILURE;
    }

    if (!$purchaseOrder->number) {
      $this->error("La orden de compra #{$purchaseOrderId} no tiene número asignado");
      return Command::FAILURE;
    }

    $this->info("Sincronizando invoice_dynamics para OC: {$purchaseOrder->number}");

    SyncInvoiceDynamicsJob::dispatch($purchaseOrder->id);

    $this->info("Job despachado exitosamente");
    return Command::SUCCESS;
  }

  /**
   * Sincroniza todas las órdenes pendientes
   * O las que están completed con NC (para detectar cambios de factura)
   */
  protected function syncAllPurchaseOrders(): int
  {
    // Validar límite de jobs pendientes antes de despachar
    if (!$this->canDispatchMoreJobs(SyncInvoiceDynamicsJob::class)) {
      return Command::SUCCESS;
    }

    // Obtener OCs que:
    // 1. No tienen invoice_dynamics (flujo normal)
    // 2. Están completed y tienen credit_note_dynamics (para detectar cambio de factura)
    $maxAttempts    = 48;
    $cooldown30     = now()->subMinutes(30);
    $oneHourAgo     = now()->subHour();
    $maxMigratedAge = now()->subDays(30);

    $purchaseOrders = PurchaseOrder::where(function ($query) {
      $query->where(function ($q) {
        // Caso 1: Sin invoice
        $q->whereNull('invoice_dynamics')
          ->orWhere('invoice_dynamics', '');
      })->orWhere(function ($q) {
        // Caso 2: Completed con NC (para detectar cambio de factura)
        $q->where('migration_status', 'completed')
          ->whereNotNull('credit_note_dynamics')
          ->where('credit_note_dynamics', '!=', '');
      });
    })
      ->whereNotNull('number')
      ->whereNotNull('migrated_at')
      ->where('migrated_at', '>=', $maxMigratedAge)
      ->where(function ($q) use ($oneHourAgo, $cooldown30, $maxAttempts) {
        // OC reciente (<1h desde migración) → siempre incluir, sin cooldown
        $q->where('migrated_at', '>', $oneHourAgo)
          // OC antigua (≥1h desde migración) → cooldown 30 min + límite de intentos
          ->orWhere(function ($inner) use ($oneHourAgo, $cooldown30, $maxAttempts) {
            $inner->where('migrated_at', '<=', $oneHourAgo)
              ->where('invoice_sync_attempts', '<', $maxAttempts)
              ->where(function ($innermost) use ($cooldown30) {
                $innermost->whereNull('invoice_sync_attempted_at')
                  ->orWhere('invoice_sync_attempted_at', '<', $cooldown30);
              });
          });
      })
      ->orderBy('invoice_sync_attempted_at', 'asc')
      ->orderBy('id', 'asc')
      ->get();

    if ($purchaseOrders->isEmpty()) {
      $this->info('No hay órdenes de compra pendientes de sincronizar invoice_dynamics');
      return Command::SUCCESS;
    }

    $this->info("Despachando jobs para sincronizar {$purchaseOrders->count()} órdenes de compra");

    $bar = $this->output->createProgressBar($purchaseOrders->count());
    $bar->start();

    foreach ($purchaseOrders as $order) {
      SyncInvoiceDynamicsJob::dispatch($order->id);
      $bar->advance();
    }

    $bar->finish();
    $this->newLine();
    foreach ($purchaseOrders as $order) {
      $this->info('Sincronizando invoice_dynamics para OC: ' . $order->number . ' (migrada: ' . $order->migrated_at . ')');
    }
    $this->info("Jobs despachados exitosamente");
    return Command::SUCCESS;
  }
}
