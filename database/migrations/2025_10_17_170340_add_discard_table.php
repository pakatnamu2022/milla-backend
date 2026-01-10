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
    Schema::table('potential_buyers', function (Blueprint $table) {
      $table->foreignId('reason_discarding_id')->after('user_id')->nullable()
        ->constrained('ap_masters')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('potential_buyers', function (Blueprint $table) {
      $table->dropForeign(['reason_discarding_id']);
      $table->dropColumn('reason_discarding_id');
    });
  }
};
