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
      $this->info("Procesando OC: {$order->number} (ID: {$order->id})");

      try {
        UpdatePurchaseOrderWithCreditNoteJob::dispatch($order->id);
        $this->info("✓ Job despachado para OC {$order->number}");
      } catch (\Exception $e) {
        $this->error("✗ Error al despachar job para OC {$order->number}: {$e->getMessage()}");
      }
    }

    $this->info('Proceso completado');
    return Command::SUCCESS;
  }
}

