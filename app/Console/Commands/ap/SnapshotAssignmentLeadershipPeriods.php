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
    $assignments = DB::table('ap_assignment_leadership_periods')
      ->where('year', $previousYear)
      ->where('month', $previousMonthNumber)
      ->get();

    if ($assignments->isEmpty()) {
      $this->warn("No hay registros del mes {$previousMonthNumber}/{$previousYear} para copiar");
      return CommandAlias::FAILURE;
    }

    // Verificar si ya existen registros para el mes actual
    $alreadyExists = DB::table('ap_assignment_leadership_periods')
      ->where('year', $currentYear)
      ->where('month', $currentMonth)
      ->exists();

    if ($alreadyExists) {
      $this->warn("Ya existen registros para {$currentMonth}/{$currentYear}");
      return CommandAlias::FAILURE;
    }

    $count = 0;
    foreach ($assignments as $a) {
      DB::table('ap_assignment_leadership_periods')->insert([
        'boss_id' => $a->boss_id,
        'worker_id' => $a->worker_id,
        'year' => $currentYear,        // ← Mes ACTUAL
        'month' => $currentMonth,       // ← Mes ACTUAL
        'status' => $a->status,
        'created_at' => now(),
        'updated_at' => now()
      ]);
      $count++;
    }

    $this->info("Copiados {$count} registros de {$previousMonthNumber}/{$previousYear} a {$currentMonth}/{$currentYear}");
    return CommandAlias::SUCCESS;
  }
}
