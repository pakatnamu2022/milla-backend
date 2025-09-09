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
    $assignments = DB::table('ap_assign_company_branch')->get();

    foreach ($assignments as $a) {
      DB::table('ap_assign_company_branch')->updateOrInsert(
        [
          'company_branch_id' => $a->company_branch_id,
          'asesor_id' => $a->asesor_id,
          'anio' => $year,
          'mes' => $month,
        ],
        ['created_at' => now(), 'updated_at' => now()]
      );
    }

    $this->info("Snapshot mensual de asignaciones tomada para $month/$year");
  }
}
