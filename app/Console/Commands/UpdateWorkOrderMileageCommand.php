<?php

namespace App\Console\Commands;

use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\WorkOrderVehicleInspection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateWorkOrderMileageCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'workorder:update-mileage {--force : Ejecutar sin confirmación}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Actualiza el kilometraje de las órdenes de trabajo según el kilometraje de sus inspecciones de vehículos';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $this->info('🔍 Analizando órdenes de trabajo con kilometraje diferente...');
    $this->newLine();

    // Obtener las relaciones activas (no canceladas) con kilometrajes diferentes
    $pivots = WorkOrderVehicleInspection::with([
      'workOrder:id,correlative,mileage',
      'vehicleInspection:id,mileage'
    ])
      ->where('is_cancelled', false)
      ->get()
      ->filter(function ($pivot) {
        // Filtrar solo los que tienen kilometrajes diferentes
        return $pivot->workOrder &&
          $pivot->vehicleInspection &&
          $pivot->workOrder->mileage != $pivot->vehicleInspection->mileage;
      });

    if ($pivots->isEmpty()) {
      $this->info('✅ No se encontraron órdenes de trabajo con kilometraje diferente.');
      $this->info('Todas las órdenes están sincronizadas con sus inspecciones.');
      return 0;
    }

    $this->warn("Se encontraron {$pivots->count()} órdenes de trabajo con kilometraje diferente:");
    $this->newLine();

    // Preparar datos para la tabla de previsualización
    $previewData = [];
    foreach ($pivots as $pivot) {
      $previewData[] = [
        'OT ID' => $pivot->workOrder->id,
        'OT Correlativo' => $pivot->workOrder->correlative,
        'Kilometraje Actual (OT)' => number_format($pivot->workOrder->mileage ?? 0, 0, '.', ',') . ' km',
        'Kilometraje Nuevo (Inspección)' => number_format($pivot->vehicleInspection->mileage ?? 0, 0, '.', ',') . ' km',
        'Diferencia' => number_format(
          ($pivot->vehicleInspection->mileage ?? 0) - ($pivot->workOrder->mileage ?? 0),
          0,
          '.',
          ','
        ) . ' km',
      ];
    }

    // Mostrar tabla de previsualización
    $this->table(
      ['OT ID', 'OT Correlativo', 'Kilometraje Actual (OT)', 'Kilometraje Nuevo (Inspección)', 'Diferencia'],
      $previewData
    );

    $this->newLine();

    // Pedir confirmación si no se usa --force
    if (!$this->option('force')) {
      if (!$this->confirm('¿Deseas continuar con la actualización?', true)) {
        $this->info('❌ Actualización cancelada.');
        return 0;
      }
    }

    $this->newLine();
    $this->info('📝 Actualizando kilometrajes...');
    $this->newLine();

    $bar = $this->output->createProgressBar($pivots->count());
    $bar->start();

    $updated = 0;
    $errors = [];

    DB::beginTransaction();

    try {
      foreach ($pivots as $pivot) {
        try {
          $workOrder = $pivot->workOrder;
          $oldMileage = $workOrder->mileage;
          $newMileage = $pivot->vehicleInspection->mileage;

          // Actualizar el kilometraje
          $workOrder->mileage = $newMileage;
          $workOrder->save();

          $updated++;
        } catch (\Exception $e) {
          $errors[] = [
            'work_order_id' => $pivot->work_order_id,
            'correlative' => $workOrder->correlative ?? 'N/A',
            'error' => $e->getMessage()
          ];
        }

        $bar->advance();
      }

      DB::commit();
      $bar->finish();
      $this->newLine(2);

      // Mostrar resumen
      $this->info('✅ Actualización completada!');
      $this->info("📊 Registros actualizados: {$updated}");

      if (!empty($errors)) {
        $this->newLine();
        $this->error("❌ Registros con errores: " . count($errors));
        $this->newLine();
        foreach ($errors as $error) {
          $this->line("  - OT ID {$error['work_order_id']} (Correlativo: {$error['correlative']}): {$error['error']}");
        }
      }
    } catch (\Exception $e) {
      DB::rollBack();
      $bar->finish();
      $this->newLine(2);
      $this->error('❌ Error durante la actualización: ' . $e->getMessage());
      return 1;
    }

    return 0;
  }
}