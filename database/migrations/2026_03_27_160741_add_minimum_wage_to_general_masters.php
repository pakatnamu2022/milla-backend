<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  public function up(): void
  {
    DB::table('general_masters')->insert([
      'id' => 13,
      'code' => 'SALARIO_MINIMO',
      'description' => 'SALARIO MINIMO',
      'type' => 'PLANILLA',
      'value' => '1130',
      'status' => true,
      'created_at' => now(),
      'updated_at' => now(),
    ]);
  }

  public function down(): void
  {
    DB::table('general_masters')->where('id', 13)->delete();
  }
};
