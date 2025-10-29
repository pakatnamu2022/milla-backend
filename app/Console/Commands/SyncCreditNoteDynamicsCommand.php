<?php

namespace App\Console\Commands;

use App\Jobs\SyncCreditNoteDynamicsJob;
use App\Models\ap\compras\PurchaseOrder;
use Illuminate\Console\Command;

class SyncCreditNoteDynamicsCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'po:sync-credit-note-dynamics {--id= : ID de la orden de compra específica} {--all : Sincronizar todas las OC sin credit_note_dynamics}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Sincroniza el campo credit_note_dynamics consultando el PA de Dynamics';

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

    if (empty($purchaseOrder->invoice_dynamics)) {
      $this->warn("La orden de compra {$purchaseOrder->number} no tiene invoice_dynamics. No se puede sincronizar nota de crédito sin factura.");
      return Command::SUCCESS;
    }

    $this->info("Sincronizando credit_note_dynamics para OC: {$purchaseOrder->number}");

    SyncCreditNoteDynamicsJob::dispatch($purchaseOrder->id);

    $this->info("Job despachado exitosamente");
    return Command::SUCCESS;
  }

  /**
   * Sincroniza todas las órdenes pendientes que tienen invoice_dynamics
   */
  protected function syncAllPurchaseOrders(): int
  {
    $count = PurchaseOrder::where(function ($query) {
      $query->whereNull('credit_note_dynamics')
        ->orWhere('credit_note_dynamics', '');
    })
      ->whereNotNull('number')
      ->count();

    if ($count === 0) {
      $this->info('No hay órdenes de compra pendientes de sincronizar credit_note_dynamics');
      return Command::SUCCESS;
    }

    $this->info("Despachando job para sincronizar {$count} órdenes de compra");

    SyncCreditNoteDynamicsJob::dispatch();

    $this->info("Job despachado exitosamente");
    return Command::SUCCESS;
  }
}
