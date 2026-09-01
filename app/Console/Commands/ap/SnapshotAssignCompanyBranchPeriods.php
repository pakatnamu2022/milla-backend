<?php

namespace App\Console\Commands\ap;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SnapshotAssignCompanyBranchPeriods extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'app:snapshot-assign-company-branch-periods';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Genera una snapshot de la asignación de sedes a asesores para el mes actual';

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
    $assignments = DB::table('ap_assign_company_branch_period')
      ->join('rrhh_persona', 'ap_assign_company_branch_period.worker_id', '=', 'rrhh_persona.id')
      ->where('ap_assign_company_branch_period.year', $previousYear)
      ->where('ap_assign_company_branch_period.month', $previousMonthNumber)
      ->where('ap_assign_company_branch_period.status', true)
      ->where('rrhh_persona.status_id', 22)
      ->whereNull('ap_assign_company_branch_period.deleted_at')
      ->select('ap_assign_company_branch_period.*')
      ->get();

    if ($assignments->isEmpty()) {
      $this->warn("No hay registros del mes {$previousMonthNumber}/{$previousYear} para copiar");
      return CommandAlias::FAILURE;
    }

    $inserted = 0;
    $skipped = 0;
    foreach ($assignments as $a) {
      $affected = DB::table('ap_assign_company_branch_period')->insertOrIgnore([
        'sede_id'    => $a->sede_id,
        'worker_id'  => $a->worker_id,
        'year'       => $currentYear,
        'month'      => $currentMonth,
        'status'     => $a->status,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
      $affected ? $inserted++ : $skipped++;
    }

    $this->info("Copiados {$inserted} registros de {$previousMonthNumber}/{$previousYear} a {$currentMonth}/{$currentYear} ({$skipped} ya existían)");
    return CommandAlias::SUCCESS;
  }
}
