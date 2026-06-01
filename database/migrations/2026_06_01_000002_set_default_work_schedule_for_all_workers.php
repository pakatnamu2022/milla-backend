<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  public function up(): void
  {
    DB::table('rrhh_persona')
      ->whereNull('work_schedule_id')
      ->update(['work_schedule_id' => 1]);
  }

  public function down(): void
  {
    DB::table('rrhh_persona')->update(['work_schedule_id' => null]);
  }
};
