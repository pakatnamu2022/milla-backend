<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('gh_evaluation_person_cycle_detail', function (Blueprint $table) {
      $table->boolean('isAscending')->default(true)->after('objective_id');
      $table->string('metric')->after('goal');
      $table->date('end_date_objectives')->after('metric');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_evaluation_person_cycle_detail', function (Blueprint $table) {
      $table->dropColumn(['isAscending', 'metric', 'end_date_objectives']);
    });
  }
};
