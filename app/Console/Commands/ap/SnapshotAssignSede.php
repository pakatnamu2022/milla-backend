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
    $anio = now()->year;
    $mes = now()->month;
    $asignaciones = DB::table('ap_assign_sede')->get();

    foreach ($asignaciones as $a) {
      DB::table('ap_assign_sede_periodo')->updateOrInsert(
        [
          'sede_id' => $a->sede_id,
          'asesor_id' => $a->asesor_id,
          'anio' => $anio,
          'mes' => $mes,
        ],
        ['created_at' => now(), 'updated_at' => now()]
      );
    }

    $this->info("Snapshot mensual de asignaciones tomada para $mes/$anio");
  }
}
