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
    Schema::table('appointment_planning', function (Blueprint $table) {
      $table->boolean('is_taken')->default(false)->after('phone_client');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('appointment_planning', function (Blueprint $table) {
      $table->dropColumn('is_taken');
    });
  }
};
