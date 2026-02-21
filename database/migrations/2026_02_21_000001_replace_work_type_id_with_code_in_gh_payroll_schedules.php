<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('gh_payroll_schedules', function (Blueprint $table) {
      $table->dropForeign(['work_type_id']);
      $table->dropColumn('work_type_id');
      $table->string('code', 50)->nullable()->after('worker_id')->comment('Schedule code');
    });
  }

  public function down(): void
  {
    Schema::table('gh_payroll_schedules', function (Blueprint $table) {
      $table->dropColumn('code');
      $table->foreignId('work_type_id')->comment('Work type ID')->constrained('gh_payroll_work_types', 'id')->onDelete('restrict');
    });
  }
};
