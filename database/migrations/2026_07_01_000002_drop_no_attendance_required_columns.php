<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    if (Schema::hasColumn('rrhh_persona', 'no_attendance_required')) {
      Schema::table('rrhh_persona', function (Blueprint $table) {
        $table->dropColumn('no_attendance_required');
      });
    }
  }

  public function down(): void
  {
    Schema::table('rrhh_persona', function (Blueprint $table) {
      $table->boolean('no_attendance_required')->nullable()->default(null);
    });
  }
};
