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
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->decimal('exchange_rate', 15, 6)->nullable()->change();
      $table->foreignId('exchange_rate_id')->after('supply_type')->nullable()->constrained('exchange_rate')->nullOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->dropForeign(['exchange_rate_id']);
      $table->dropColumn('exchange_rate_id');
    });
  }
};
