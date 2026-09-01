<?php

namespace App\Console\Commands\ap;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SnapshotCommercialManagerBrandGroupPeriods extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'app:snapshot-commercial-manager-brand-group-periods';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Genera una snapshot de la asignación de gerentes comerciales a grupos de marcas para el mes actual';

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
    $assignments = DB::table('ap_commercial_manager_brand_group_periods')
      ->join('rrhh_persona', 'ap_commercial_manager_brand_group_periods.commercial_manager_id', '=', 'rrhh_persona.id')
      ->where('ap_commercial_manager_brand_group_periods.year', $previousYear)
      ->where('ap_commercial_manager_brand_group_periods.month', $previousMonthNumber)
      ->where('ap_commercial_manager_brand_group_periods.status', true)
      ->where('rrhh_persona.status_id', 22)
      ->whereNull('ap_commercial_manager_brand_group_periods.deleted_at')
      ->select('ap_commercial_manager_brand_group_periods.*')
      ->get();

    if ($assignments->isEmpty()) {
      $this->warn("No hay registros del mes {$previousMonthNumber}/{$previousYear} para copiar");
      return CommandAlias::FAILURE;
    }

    $inserted = 0;
    $skipped = 0;
    foreach ($assignments as $a) {
      $affected = DB::table('ap_commercial_manager_brand_group_periods')->insertOrIgnore([
        'commercial_manager_id' => $a->commercial_manager_id,
        'brand_group_id'        => $a->brand_group_id,
        'year'                  => $currentYear,
        'month'                 => $currentMonth,
        'status'                => $a->status,
        'created_at'            => now(),
        'updated_at'            => now(),
      ]);
      $affected ? $inserted++ : $skipped++;
    }

    $this->info("Copiados {$inserted} registros de {$previousMonthNumber}/{$previousYear} a {$currentMonth}/{$currentYear} ({$skipped} ya existían)");
    return CommandAlias::SUCCESS;
  }
}
