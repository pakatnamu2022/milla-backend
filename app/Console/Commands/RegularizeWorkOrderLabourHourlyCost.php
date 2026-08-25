<?php

namespace App\Console\Commands;

use App\Models\ap\postventa\taller\WorkOrderLabour;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RegularizeWorkOrderLabourHourlyCost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'work-order-labour:regularize-hourly-cost {--dry-run : Ejecutar en modo simulación sin guardar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regulariza el campo current_hourly_cost de los registros de mano de obra existentes según el tipo de vehículo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Ejecutando en modo DRY-RUN (sin guardar cambios)...');
            $this->newLine();
        }

        $this->info('Buscando registros de mano de obra sin current_hourly_cost...');

        // Buscar registros donde current_hourly_cost sea NULL o 0
        $labours = WorkOrderLabour::with('workOrder.vehicle')
            ->where(function ($query) {
                $query->whereNull('current_hourly_cost')
                    ->orWhere('current_hourly_cost', 0);
            })
            ->get();

        $totalRecords = $labours->count();

        if ($totalRecords === 0) {
            $this->info('✅ No se encontraron registros para regularizar.');
            return 0;
        }

        $this->info("📊 Se encontraron {$totalRecords} registros para regularizar.");
        $this->newLine();

        if (!$dryRun && !$this->confirm('¿Deseas continuar con la regularización?', true)) {
            $this->warn('❌ Operación cancelada por el usuario.');
            return 1;
        }

        $this->newLine();
        $progressBar = $this->output->createProgressBar($totalRecords);
        $progressBar->start();

        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($labours as $labour) {
            try {
                // Verificar si tiene work_order asociada
                if (!$labour->workOrder) {
                    $skipped++;
                    $errors[] = "ID {$labour->id}: No tiene orden de trabajo asociada";
                    $progressBar->advance();
                    continue;
                }

                // Obtener el costo actual usando el método centralizado
                $currentHourlyCost = $labour->workOrder->getCurrentHourlyCost();

                if (!$dryRun) {
                    // Actualizar sin disparar eventos ni timestamps
                    DB::table('work_order_labour')
                        ->where('id', $labour->id)
                        ->update(['current_hourly_cost' => $currentHourlyCost]);
                }

                $updated++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = "ID {$labour->id}: {$e->getMessage()}";
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Mostrar resumen
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📋 RESUMEN DE LA REGULARIZACIÓN');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->table(
            ['Concepto', 'Cantidad'],
            [
                ['Total registros encontrados', $totalRecords],
                ['✅ Actualizados' . ($dryRun ? ' (simulado)' : ''), $updated],
                ['⚠️  Omitidos', $skipped],
            ]
        );

        if (count($errors) > 0) {
            $this->newLine();
            $this->warn('⚠️  Errores encontrados:');
            foreach ($errors as $error) {
                $this->line("  • {$error}");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('💡 Este fue un DRY-RUN. Ejecuta el comando sin --dry-run para aplicar los cambios.');
        } else {
            $this->newLine();
            $this->info('✅ Regularización completada exitosamente!');
        }

        return 0;
    }
}
