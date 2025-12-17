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
    Schema::table('gh_per_diem_request', function (Blueprint $table) {
      // Remove destination column
      $table->dropColumn('destination');

      // Add district_id column with foreign key reference
      $table->foreignId('district_id')
        ->after('company_id')
        ->comment('Reference to the district')
        ->constrained('district')
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_per_diem_request', function (Blueprint $table) {
      // Drop district_id foreign key and column
      $table->dropForeign(['district_id']);
      $table->dropColumn('district_id');

      // Restore destination column
      $table->string('destination')
        ->after('company_id')
        ->comment('Destination for the per diem request');
    });
  }
};
