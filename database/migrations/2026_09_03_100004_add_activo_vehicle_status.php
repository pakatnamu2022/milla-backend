<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  public function up(): void
  {
    $exists = DB::table('ap_vehicle_status')->where('id', 11)->exists();
    if (!$exists) {
      DB::table('ap_vehicle_status')->insert([
        'id'          => 11,
        'code'        => 'ACT',
        'description' => 'ACTIVO',
        'use'         => 'TALLER',
        'color'       => '#0EA5E9',
        'status'      => 1,
        'created_at'  => now(),
        'updated_at'  => now(),
      ]);
    }
  }

  public function down(): void
  {
    DB::table('ap_vehicle_status')->where('id', 11)->where('description', 'ACTIVO')->delete();
  }
};
