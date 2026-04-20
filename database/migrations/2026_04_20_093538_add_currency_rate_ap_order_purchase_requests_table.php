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
    Schema::table('ap_order_purchase_requests', function (Blueprint $table) {
      $table->foreignId('currency_id')->nullable()->after('warehouse_id')->constrained('type_currency')->onDelete('set null');
      $table->decimal('exchange_rate', 15, 6)->nullable()->after('currency_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_purchase_requests', function (Blueprint $table) {
      $table->dropForeign(['currency_id']);
      $table->dropColumn('currency_id');
      $table->dropColumn('exchange_rate');
    });
  }
};
