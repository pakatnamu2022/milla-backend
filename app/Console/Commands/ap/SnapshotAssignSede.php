<?php

namespace App\Console\Commands\ap;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SnapshotAssignSede extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'app:snapshot-assign-sede';

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
    $year = now()->year;
    $month = now()->month;
    $assignments = DB::table('ap_assign_company_branch_period')->get();

    foreach ($assignments as $a) {
      DB::table('ap_assign_company_branch_period')->updateOrInsert(
        [
          'sede_id' => $a->sede_id,
          'worker_id' => $a->worker_id,
          'year' => $year,
          'month' => $month, // Snapshot del mes anterior
        ],
        ['created_at' => now(), 'updated_at' => now()]
      );
    }

    $this->info("Snapshot mensual de asignaciones tomada para $month/$year");
  }
}
