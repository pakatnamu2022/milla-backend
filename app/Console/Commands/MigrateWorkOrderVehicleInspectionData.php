<?php

namespace App\Console\Commands;

use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\WorkOrderVehicleInspection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateWorkOrderVehicleInspectionData extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'migrate:work-order-vehicle-inspection
                          {--dry-run : Ejecutar en modo de prueba sin guardar cambios}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Migra los datos de las relaciones directas entre órdenes de trabajo e inspecciones vehiculares a la tabla pivot work_order_vehicle_inspection';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $isDryRun = $this->option('dry-run');

    if ($isDryRun) {
      $this->warn('🔍 MODO DRY-RUN: No se guardarán cambios en la base de datos');
    }

    $this->info('📋 Iniciando migración de datos...');
    $this->newLine();

    return DB::transaction(function () use ($isDryRun) {
      // Contar registros existentes en la tabla pivot
      $existingPivotCount = WorkOrderVehicleInspection::count();
      $this->info("📊 Registros existentes en work_order_vehicle_inspection: {$existingPivotCount}");
      $this->newLine();

      // ========================================
      // MIGRAR desde ap_work_orders.vehicle_inspection_id
      // ========================================
      $this->info('🔄 Migrando desde ap_work_orders.vehicle_inspection_id...');

      $workOrders = ApWorkOrder::whereNotNull('vehicle_inspection_id')
        ->with('vehicleInspection')
        ->get();

      $this->info("   Encontradas {$workOrders->count()} órdenes de trabajo con inspección asignada");

      $migrated = 0;
      $skipped = 0;
      $mileageUpdated = 0;

      $progressBar = $this->output->createProgressBar($workOrders->count());
      $progressBar->start();

      foreach ($workOrders as $workOrder) {
        // Verificar si ya existe en la tabla pivot
        $exists = WorkOrderVehicleInspection::where('work_order_id', $workOrder->id)
          ->where('vehicle_inspection_id', $workOrder->vehicle_inspection_id)
          ->exists();

        if ($exists) {
          $skipped++;
        } else {
          // Crear registro en la tabla pivot
          if (!$isDryRun) {
            WorkOrderVehicleInspection::create([
              'work_order_id' => $workOrder->id,
              'vehicle_inspection_id' => $workOrder->vehicle_inspection_id,
              'is_cancelled' => false,
              'created_at' => $workOrder->created_at,
              'updated_at' => $workOrder->updated_at,
            ]);
          }
          $migrated++;
        }

        // Actualizar el mileage de la orden de trabajo con el valor de la recepción
        if ($workOrder->vehicleInspection && $workOrder->vehicleInspection->mileage) {
          $inspectionMileage = $workOrder->vehicleInspection->mileage;

          // Solo actualizar si es diferente o si la orden no tiene mileage
          if ($workOrder->mileage != $inspectionMileage) {
            if (!$isDryRun) {
              $workOrder->update(['mileage' => $inspectionMileage]);
            }
            $mileageUpdated++;
          }
        }

        $progressBar->advance();
      }

      $progressBar->finish();
      $this->newLine(2);

      $this->info("   ✅ Migrados a pivot: {$migrated}");
      $this->info("   ⏭️  Omitidos (ya existían): {$skipped}");
      $this->info("   🔄 Kilometraje actualizado: {$mileageUpdated}");
      $this->newLine();

      // ========================================
      // RESUMEN FINAL
      // ========================================
      $finalCount = WorkOrderVehicleInspection::count();

      $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
      $this->info('📊 RESUMEN DE MIGRACIÓN');
      $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
      $this->info("   Registros iniciales en pivot: {$existingPivotCount}");
      $this->info("   Migrados a pivot: {$migrated}");
      $this->info("   Omitidos (ya existían): {$skipped}");
      $this->info("   Kilometraje actualizado: {$mileageUpdated}");
      $this->info("   Registros finales en pivot: " . ($isDryRun ? $existingPivotCount : $finalCount));
      $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
      $this->newLine();

      if ($isDryRun) {
        $this->warn('⚠️  DRY-RUN: Se ha revertido la transacción. No se guardaron cambios.');
        // En dry-run, revertir la transacción
        DB::rollBack();
        return Command::SUCCESS;
      }

      $this->info('✅ Migración completada exitosamente');
      $this->newLine();
      $this->comment('📝 Siguientes pasos:');
      $this->comment('   1. Verifica que los datos migrados sean correctos');
      $this->comment('   2. Ejecuta las migraciones para eliminar las columnas antiguas');
      $this->comment('   3. Actualiza el código para usar solo la tabla pivot');

      return Command::SUCCESS;
    });
  }
}
