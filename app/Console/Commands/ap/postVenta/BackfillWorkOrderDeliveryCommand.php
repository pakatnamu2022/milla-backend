<?php

namespace App\Console\Commands\ap\postVenta;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\taller\ApWorkOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillWorkOrderDeliveryCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'ap:backfill-work-order-delivery
    {--id= : ID de una orden de trabajo específica a actualizar}
    {--dry-run : Muestra un preview de lo que se actualizaría sin modificar datos}
    {--all : Actualiza todas las órdenes de trabajo que cumplan los criterios}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Rellena los campos de entrega (actual_delivery_date, is_delivery, delivery_by, post_service_follow_up, notes_delivery) de órdenes de trabajo cerradas sin entrega, tomando la fecha del comprobante electrónico asociado';

  private const DEFAULT_FOLLOW_UP = '[{"days":1,"time_start":"08:00","time_end":"10:00","scheduled_datetime_start":"2026-07-09 08:00:00","scheduled_datetime_end":"2026-07-09 10:00:00","completed":false},{"days":4,"time_start":"08:00","time_end":"10:00","scheduled_datetime_start":"2026-07-12 08:00:00","scheduled_datetime_end":"2026-07-12 10:00:00","completed":false}]';

  /**
   * Execute the console command.
   */
  public function handle(): int
  {
    $id = $this->option('id');
    $dryRun = (bool) $this->option('dry-run');
    $all = (bool) $this->option('all');

    if (!$id && !$dryRun && !$all) {
      $this->error('Debes especificar una opción: --dry-run (preview), --id=X (una orden específica) o --all (todas).');
      return 1;
    }

    $query = ApWorkOrder::query()
      ->where('status_id', ApMasters::CLOSED_WORK_ORDER_ID)
      ->where('is_delivery', false)
      ->with('advisor.user')
      ->orderBy('id');

    if ($id) {
      $query->where('id', $id);
    }

    $workOrders = $query->get();

    if ($workOrders->isEmpty()) {
      $this->info($id
        ? "La orden de trabajo #{$id} no existe, no está cerrada o ya tiene una entrega generada."
        : 'No se encontraron órdenes de trabajo cerradas pendientes de entrega.');
      return 0;
    }

    $planned = [];
    $skippedNoDocument = [];
    $skippedNoUser = [];

    foreach ($workOrders as $workOrder) {
      $document = ElectronicDocument::where('work_order_id', $workOrder->id)
        ->orderByDesc('created_at')
        ->first();

      if (!$document) {
        $skippedNoDocument[] = $workOrder->id;
        continue;
      }

      $deliveryBy = $workOrder->advisor?->user?->id;

      if (!$deliveryBy) {
        $skippedNoUser[] = $workOrder->id;
        continue;
      }

      $planned[] = [
        'work_order_id' => $workOrder->id,
        'correlative' => $workOrder->correlative,
        'actual_delivery_date' => $document->created_at->format('Y-m-d H:i:s'),
        'is_delivery' => true,
        'delivery_by' => $deliveryBy,
        'post_service_follow_up' => self::DEFAULT_FOLLOW_UP,
        'notes_delivery' => '',
      ];
    }

    if ($dryRun) {
      $this->printSummary($workOrders->count(), $planned, $skippedNoDocument, $skippedNoUser);
      $this->printPreviewTable(array_slice($planned, 0, 5));
      return 0;
    }

    if (empty($planned)) {
      $this->printSummary($workOrders->count(), $planned, $skippedNoDocument, $skippedNoUser);
      return 0;
    }

    if ($all && !$id) {
      $this->printSummary($workOrders->count(), $planned, $skippedNoDocument, $skippedNoUser);
      $this->printPreviewTable(array_slice($planned, 0, 5));

      if (!$this->confirm("¿Deseas actualizar las {$this->formatCount(count($planned))} órdenes de trabajo listadas?")) {
        $this->info('Actualización cancelada.');
        return 0;
      }
    }

    $updated = 0;
    $errors = [];
    $bar = $this->output->createProgressBar(count($planned));
    $bar->start();

    foreach ($planned as $item) {
      try {
        DB::transaction(function () use ($item) {
          ApWorkOrder::whereKey($item['work_order_id'])->update([
            'actual_delivery_date' => $item['actual_delivery_date'],
            'is_delivery' => $item['is_delivery'],
            'delivery_by' => $item['delivery_by'],
            'post_service_follow_up' => $item['post_service_follow_up'],
            'notes_delivery' => $item['notes_delivery'],
          ]);
        });
        $updated++;
      } catch (\Exception $e) {
        $errors[] = [
          'work_order_id' => $item['work_order_id'],
          'error' => $e->getMessage(),
        ];
      }

      $bar->advance();
    }

    $bar->finish();
    $this->newLine(2);

    $this->info("Órdenes de trabajo actualizadas: {$updated}");

    if (!empty($skippedNoDocument)) {
      $this->warn('Omitidas por no tener comprobante electrónico asociado: ' . count($skippedNoDocument));
      $this->line(implode(', ', $skippedNoDocument));
    }

    if (!empty($skippedNoUser)) {
      $this->warn('Omitidas por no tener asesor/usuario asociado: ' . count($skippedNoUser));
      $this->line(implode(', ', $skippedNoUser));
    }

    if (!empty($errors)) {
      $this->error('Errores durante la actualización:');
      foreach ($errors as $error) {
        $this->line("  - Orden #{$error['work_order_id']}: {$error['error']}");
      }
    }

    return empty($errors) ? 0 : 1;
  }

  private function printSummary(int $total, array $planned, array $skippedNoDocument, array $skippedNoUser): void
  {
    $this->info("Órdenes de trabajo detectadas (status = cerrada, is_delivery = false): {$total}");
    $this->info('Se actualizarían: ' . count($planned));

    if (!empty($skippedNoDocument)) {
      $this->warn('Sin comprobante electrónico (se omitirían): ' . count($skippedNoDocument) . ' -> ' . implode(', ', $skippedNoDocument));
    }

    if (!empty($skippedNoUser)) {
      $this->warn('Sin asesor/usuario asociado (se omitirían): ' . count($skippedNoUser) . ' -> ' . implode(', ', $skippedNoUser));
    }
  }

  private function printPreviewTable(array $rows): void
  {
    if (empty($rows)) {
      return;
    }

    $this->newLine();
    $this->info('Preview de las primeras ' . count($rows) . ' órdenes a actualizar:');
    $this->table(
      ['ID', 'Correlativo', 'actual_delivery_date', 'is_delivery', 'delivery_by', 'notes_delivery', 'post_service_follow_up'],
      array_map(fn($row) => [
        $row['work_order_id'],
        $row['correlative'],
        $row['actual_delivery_date'],
        $row['is_delivery'] ? 'true' : 'false',
        $row['delivery_by'],
        $row['notes_delivery'] === '' ? '(vacío)' : $row['notes_delivery'],
        $row['post_service_follow_up'],
      ], $rows)
    );
  }

  private function formatCount(int $count): string
  {
    return $count . ($count === 1 ? ' orden' : ' órdenes');
  }
}
