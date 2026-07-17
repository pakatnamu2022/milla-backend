<?php

namespace App\Console\Commands;

use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use Illuminate\Console\Command;

/**
 * Simula (sin ejecutar) la salida de inventario que generaría
 * SyncAccountingStatusJob::createInventoryMovementForQuotation() para una
 * cotización de repuesto (Postventa). No escribe nada en base de datos.
 */
class SimulateQuotationInventoryOutputCommand extends Command
{
  protected $signature = 'quotation:simulate-inventory-output
    {quotationId : ID de la cotización (ApOrderQuotations)}
    {--assume-paid : Ignora los checks de salida previa/factura final/contabilización y muestra qué saldría si ya estuviera todo pagado}';

  protected $description = 'Simula la salida de inventario de una cotización de repuesto y muestra qué se movería y si libera cantidad reservada, sin ejecutar nada';

  public function handle(ProductWarehouseStockService $stockService): int
  {
    $quotationId = (int) $this->argument('quotationId');
    $assumePaid = (bool) $this->option('assume-paid');

    $quotation = ApOrderQuotations::with(['details.product', 'sede', 'client'])->find($quotationId);

    if (!$quotation) {
      $this->error("Cotización #{$quotationId} no encontrada.");
      return 1;
    }

    $this->info("=== SIMULACIÓN de salida de inventario — Cotización #{$quotation->id} ({$quotation->quotation_number}) ===");
    $this->line("Estado actual: {$quotation->status}");
    $this->line('Sede: ' . ($quotation->sede->name ?? $quotation->sede_id));

    if ($assumePaid) {
      $this->warn('Modo --assume-paid: se ignoran los checks reales de factura final/contabilización. Esto es HIPOTÉTICO, no refleja el estado actual del job.');
    }

    // 1) Ya generó salida
    if ($quotation->output_generation_warehouse && !$assumePaid) {
      $this->warn('output_generation_warehouse = true → El job NO generaría movimiento (ya se generó antes).');
      return 0;
    }

    // 2) Factura final
    $finalInvoice = $quotation->getFinalInvoice();

    if ($finalInvoice) {
      $this->line("Factura final detectada: #{$finalInvoice->id} ({$finalInvoice->full_number}) — is_accounted=" . ($finalInvoice->is_accounted ? 'true' : 'false'));
    } else {
      $this->line('Factura final detectada: ninguna todavía.');
    }

    if (!$assumePaid) {
      if (!$finalInvoice) {
        $this->warn('No existe factura final (is_advance_payment = 0) asociada todavía.');
        $this->line('El job saldría sin hacer nada (return por falta de factura final).');
        return 0;
      }

      if (!$finalInvoice->is_accounted) {
        $this->warn('La factura final aún NO está contabilizada en Dynamics.');
        $this->line('El job saldría sin hacer nada (return por factura no contabilizada).');
        return 0;
      }
    }

    // 3) Almacén de la sede
    $warehouse = Warehouse::where('sede_id', $quotation->sede_id)
      ->where('is_physical_warehouse', true)
      ->where('status', true)
      ->first();

    if (!$warehouse) {
      $this->error("No se encontró almacén físico activo para la sede #{$quotation->sede_id}.");
      return 1;
    }

    $this->line("Almacén destino de la salida: #{$warehouse->id} ({$warehouse->name})");

    // 4) Detalles de producto (excluye mano de obra)
    $productDetails = $quotation->details->where('item_type', '!=', 'LABOR')->where('product_id', '!=', null);

    if ($productDetails->isEmpty()) {
      $this->warn('La cotización no contiene productos (solo mano de obra). No se generaría movimiento de inventario.');
      return 0;
    }

    $this->newLine();
    $this->info('--- Detalle de movimiento simulado (tipo SALE, saliente) ---');

    $rows = [];
    $totalQuantity = 0;
    $blockingErrors = [];

    foreach ($productDetails as $detail) {
      $stock = $stockService->getStock($detail->product_id, $warehouse->id);
      $productName = $detail->product->name ?? "producto #{$detail->product_id}";

      if (!$stock) {
        $blockingErrors[] = "Sin registro de stock para '{$productName}' en el almacén #{$warehouse->id}.";
        $rows[] = [$productName, $detail->supply_type, $detail->quantity, '-', '-', '-', 'SIN STOCK'];
        continue;
      }

      $isStockType = $detail->supply_type === ApOrderQuotations::STOCK;

      // Misma validación que createSaleFromQuotation()
      if ($isStockType) {
        $stockOk = $stock->quantity >= $detail->quantity;
        $validationBasis = 'quantity (físico)';
      } else {
        $stockOk = $stock->available_quantity >= $detail->quantity;
        $validationBasis = 'available_quantity (libre)';
      }

      if (!$stockOk) {
        $blockingErrors[] = "Stock insuficiente para '{$productName}' (supply_type={$detail->supply_type}, base={$validationBasis}).";
      }

      // Simulación de liberación de reserva: solo aplica a supply_type = STOCK
      $releasesReserved = $isStockType;
      $reservedAfter = $releasesReserved
        ? max(0, $stock->reserved_quantity - $detail->quantity)
        : $stock->reserved_quantity;

      $physicalAfter = $stock->quantity - $detail->quantity;

      $rows[] = [
        $productName,
        $detail->supply_type,
        $detail->quantity,
        "{$stock->quantity} → {$physicalAfter}",
        $releasesReserved ? "{$stock->reserved_quantity} → {$reservedAfter}" : "{$stock->reserved_quantity} (sin cambio)",
        $releasesReserved ? 'SÍ (libera reserva)' : 'NO (nunca se reservó)',
        $stockOk ? 'OK' : 'INSUFICIENTE',
      ];

      $totalQuantity += $detail->quantity;
    }

    $this->table(
      ['Producto', 'Supply Type', 'Cant. a mover', 'Stock físico (antes→después)', 'Reservado (antes→después)', '¿Libera reservado?', 'Validación'],
      $rows
    );

    $this->newLine();
    $this->line("Total ítems: {$productDetails->count()} | Total cantidad a salir: {$totalQuantity}");

    if (!empty($blockingErrors)) {
      $this->newLine();
      $this->error('El movimiento fallaría por:');
      foreach ($blockingErrors as $err) {
        $this->line(" - {$err}");
      }
      return 1;
    }

    $this->newLine();
    if ($assumePaid) {
      $this->info('Resultado (HIPOTÉTICO --assume-paid): si esta cotización estuviera con factura final contabilizada, esto es lo que saldría de inventario.');
    } else {
      $this->info('Resultado: el job SÍ generaría la salida de inventario (movement_type=SALE) para esta cotización.');
    }
    $this->line('Acciones que realizaría (no ejecutadas en esta simulación):');
    $this->line(' 1. Crear InventoryMovement (header) + InventoryMovementDetail por cada producto.');
    $this->line(' 2. Marcar cotización: is_fully_paid=true, status=Facturado, output_generation_warehouse=true.');
    $this->line(' 3. Para ítems supply_type=STOCK: liberar reserved_quantity (releaseReservedStock).');
    $this->line(' 4. Restar quantity física en ProductWarehouseStock (removeStock) para todos los ítems.');

    return 0;
  }
}
