<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  public function up(): void
  {
    // Each entry: [area_ids[], sede_localidad_keyword, work_schedule_id]
    // Conflicts resolved by keeping the first-listed schedule for the same area+sede.
    // empresa_id = 3 → AP (Automotores Pakatnamu)
    $mappings = [
      // Cajamarca
      [[16],     'Cajamarca',  1],
      [[17],     'Cajamarca',  2],
      [[18],     'Cajamarca',  1],
      [[15],     'Cajamarca',  4],

      // Chiclayo
      [[37],     'Chiclayo',   5],
      [[34],     'Chiclayo',   6],
      [[33],     'Chiclayo',   6],
      [[32],     'Chiclayo',   1],

      // Grau
      [[33, 66], 'Grau',       8],

      // Salaverry (accepts both spellings: Salaverry / Salavarry)
      [[33],     'alav',       8],
      [[65],     'alav',       9],

      // Pimentel
      [[33],     'Pimentel',   8],

      // Jaen / Jaén
      [[22],     'Jaen',       1],
      [[20],     'Jaen',       1],
      [[21],     'Jaen',       10],

      // Piura
      [[24, 25], 'Piura',      5],
      [[26, 27], 'Piura',      3],
      [[23],     'Piura',      3],
    ];

    foreach ($mappings as [$areaIds, $sedeLike, $scheduleId]) {
      $placeholders = implode(',', array_fill(0, count($areaIds), '?'));
      DB::statement(
        "UPDATE rrhh_persona p
         JOIN config_sede s ON s.id = p.sede_id
         SET p.work_schedule_id = ?
         WHERE p.area_id IN ({$placeholders})
           AND s.empresa_id = 3
           AND s.localidad LIKE ?",
        array_merge([$scheduleId], $areaIds, ["%{$sedeLike}%"])
      );
    }

    // Individual schedule overrides (after bulk, so they take precedence)
    DB::table('rrhh_persona')->where('id', 4211)->update(['work_schedule_id' => 11]);
  }

  public function down(): void
  {
    // Reset all AP workers to schedule 1 (original default)
    DB::statement(
      "UPDATE rrhh_persona p
       JOIN config_sede s ON s.id = p.sede_id
       SET p.work_schedule_id = 1
       WHERE s.empresa_id = 3"
    );
  }
};
