<?php

namespace App\Console\Commands;

use App\Jobs\UpdatePurchaseOrderWithCreditNoteJob;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use Illuminate\Console\Command;

class ProcessCreditNoteUpdatesCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'po:process-credit-note-updates';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Procesa actualizaciones pendientes de OC con NC en Dynamics';

  /**
   * Execute the console command.
   */
  public function handle(): int
  {
    $this->info('Buscando órdenes de compra con NC pendientes de sincronizar...');

    // Buscar OCs que tienen NC y no están completamente actualizadas
    $pendingOrders = VehiclePurchaseOrder::whereNotNull('credit_note_dynamics')
      ->where('credit_note_dynamics', '!=', '')
      ->where(function ($query) {
        $query->where('migration_status', '!=', 'updated_with_nc')
          ->orWhereNull('migration_status');
      })
      ->get();

    if ($pendingOrders->isEmpty()) {
      $this->info('No hay órdenes de compra con NC pendientes de procesar');
      return Command::SUCCESS;
    }

    $this->info("Encontradas {$pendingOrders->count()} órdenes de compra con NC pendientes");

    foreach ($pendingOrders as $order) {
      $this->info("Procesando OC original: {$order->number} (ID: {$order->id})");

      // Buscar la nueva OC (la que tiene el punto) relacionada
      $newOrder = VehiclePurchaseOrder::where('original_purchase_order_id', $order->id)
        ->first();

      if (!$newOrder) {
        $this->warn("⚠ No se encontró nueva OC relacionada para {$order->number}. Omitiendo...");
        continue;
      }

      $this->info("Nueva OC encontrada: {$newOrder->number} (ID: {$newOrder->id})");

      try {
        UpdatePurchaseOrderWithCreditNoteJob::dispatch($order->id, $newOrder->id);
        $this->info("✓ Job despachado para actualizar {$order->number} -> {$newOrder->number}");
      } catch (\Exception $e) {
        $this->error("✗ Error al despachar job para OC {$order->number}: {$e->getMessage()}");
      }
    }

    $this->info('Proceso completado');
    return Command::SUCCESS;
  }
}

