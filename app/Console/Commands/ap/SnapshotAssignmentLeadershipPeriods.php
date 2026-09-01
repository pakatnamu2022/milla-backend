<?php

namespace App\Console\Commands\ap;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SnapshotAssignmentLeadershipPeriods extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'app:snapshot-assignment-leadership-periods';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Genera una snapshot de la asignación de jefes a asesores para el mes actual';

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
    // Validar que tanto el jefe (boss_id) como el asesor (worker_id) estén activos (status_id = 22)
    $assignments = DB::table('ap_assignment_leadership_periods')
      ->join('rrhh_persona as boss', 'ap_assignment_leadership_periods.boss_id', '=', 'boss.id')
      ->join('rrhh_persona as worker', 'ap_assignment_leadership_periods.worker_id', '=', 'worker.id')
      ->where('ap_assignment_leadership_periods.year', $previousYear)
      ->where('ap_assignment_leadership_periods.month', $previousMonthNumber)
      ->where('ap_assignment_leadership_periods.status', true)
      ->where('boss.status_id', 22)
      ->where('worker.status_id', 22)
      ->whereNull('ap_assignment_leadership_periods.deleted_at')
      ->select('ap_assignment_leadership_periods.*')
      ->get();

    if ($assignments->isEmpty()) {
      $this->warn("No hay registros del mes {$previousMonthNumber}/{$previousYear} para copiar");
      return CommandAlias::FAILURE;
    }

    $inserted = 0;
    $skipped = 0;
    foreach ($assignments as $a) {
      $affected = DB::table('ap_assignment_leadership_periods')->insertOrIgnore([
        'boss_id'    => $a->boss_id,
        'worker_id'  => $a->worker_id,
        'year'       => $currentYear,
        'month'      => $currentMonth,
        'status'     => $a->status,
        'hierarchy'  => $a->hierarchy ?? 0,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
      $affected ? $inserted++ : $skipped++;
    }

    $this->info("Copiados {$inserted} registros de {$previousMonthNumber}/{$previousYear} a {$currentMonth}/{$currentYear} ({$skipped} ya existían)");
    return CommandAlias::SUCCESS;
  }
}
