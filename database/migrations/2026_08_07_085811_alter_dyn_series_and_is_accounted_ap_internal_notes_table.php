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
    Schema::table('ap_internal_notes', function (Blueprint $table) {
      $table->renameColumn('dyn_series', 'dyn_series_in');
      $table->string('dyn_series_out', 50)->nullable()->after('dyn_series_in');
      $table->boolean('is_accounted_in')->default(false)->after('dyn_series_out');
      $table->boolean('is_accounted_out')->default(false)->after('is_accounted_in');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_internal_notes', function (Blueprint $table) {
      $table->renameColumn('dyn_series_in', 'dyn_series_out');
      $table->dropColumn('dyn_series_out');
      $table->dropColumn('is_accounted_in');
      $table->dropColumn('is_accounted_out');
    });
  }
};
