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
    Schema::table('detailed_development_plan', function (Blueprint $table) {
      $table->string('comment', 500)->nullable()->after('description');
      $table->dropColumn(['boss_confirms', 'worker_confirms', 'boss_confirms_completion', 'worker_confirms_completion']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('detailed_development_plan', function (Blueprint $table) {
      $table->dropColumn('comment');
      $table->boolean('boss_confirms')->default(false);
      $table->boolean('worker_confirms')->default(false);
      $table->boolean('boss_confirms_completion')->default(false);
      $table->boolean('worker_confirms_completion')->default(false);
    });
  }
};
