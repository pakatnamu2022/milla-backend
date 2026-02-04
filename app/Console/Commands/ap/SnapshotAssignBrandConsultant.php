<?php

namespace App\Console\Commands\ap;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SnapshotAssignBrandConsultant extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'app:snapshot-assign-brand-consultant';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Genera una snapshot de la asignación de marcas a asesores para el mes actual';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    // Mes que acaba de terminar
    $previousMonth = now()->subMonth();
    $previousYear = $previousMonth->year;
    $previousMonthNumber = $previousMonth->month;

    // Mes actual donde vamos a copiar
    $currentYear = now()->year;
    $currentMonth = now()->month;

    // Obtener registros del mes ANTERIOR
    $assignments = DB::table('ap_assign_brand_consultant')
      ->where('year', $previousYear)
      ->where('month', $previousMonthNumber)
      ->where('status', true)
      ->get();

    if ($assignments->isEmpty()) {
      $this->warn("No hay registros del mes {$previousMonthNumber}/{$previousYear} para copiar");
      return CommandAlias::FAILURE;
    }

    // Verificar si ya existen registros para el mes actual
    $alreadyExists = DB::table('ap_assign_brand_consultant')
      ->where('year', $currentYear)
      ->where('month', $currentMonth)
      ->exists();

    if ($alreadyExists) {
      $this->warn("Ya existen registros para {$currentMonth}/{$currentYear}");
      return CommandAlias::FAILURE;
    }

    $count = 0;
    foreach ($assignments as $a) {
      DB::table('ap_assign_brand_consultant')->updateOrInsert(
        [
          'sales_target' => $a->sales_target,
          'brand_id' => $a->brand_id,
          'worker_id' => $a->worker_id,
          'sede_id' => $a->sede_id,
          'year' => $currentYear,
          'month' => $currentMonth, // Snapshot del mes anterior
        ],
        ['created_at' => now(), 'updated_at' => now()]
      );
      $count++;
    }

    $this->info("Copiados {$count} registros de {$previousMonthNumber}/{$previousYear} a {$currentMonth}/{$currentYear}");
    return CommandAlias::SUCCESS;
  }
}
