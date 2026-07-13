<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  public function up(): void
  {
    DB::table('ap_vehicle_status')->insert([
      'id'          => 10,
      'code'        => 'EN_CURSO',
      'description' => 'EN CURSO',
      'use'         => 'VENTAS',
      'color'       => '#F97316',
      'status'      => true,
      'created_at'  => now(),
      'updated_at'  => now(),
    ]);
  }

  public function down(): void
  {
    DB::table('ap_vehicle_status')->where('id', 10)->delete();
  }
};
