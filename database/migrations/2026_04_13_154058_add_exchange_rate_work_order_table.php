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
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->foreignId('exchange_rate_id')->nullable()->after('sede_id')->constrained('exchange_rate')->onDelete('set null');
      $table->decimal('exchange_rate')->nullable()->after('exchange_rate_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->dropForeign(['exchange_rate_id']);
      $table->dropColumn(['exchange_rate', 'exchange_rate_id']);
    });
  }
};
