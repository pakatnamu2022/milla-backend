<?php

namespace App\Console\Commands\ap;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SnapshotAssignCompanyBranch extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'app:snapshot-assign-company-branch';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Command description';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $year = now()->year;
    $month = now()->month;
    $assignments = DB::table('ap_assignment_leadership_periods')->get();

    foreach ($assignments as $a) {
      DB::table('ap_assignment_leadership_periods')->updateOrInsert(
        [
          'boss_id' => $a->boss_id,
          'worker_id' => $a->worker_id,
          'year' => $year,
          'month' => $month, // Snapshot del mes anterior
          'status' => true
        ],
        ['created_at' => now(), 'updated_at' => now()]
      );
    }

    $this->info("Snapshot mensual de asignaciones tomada para $month/$year");
  }
}
