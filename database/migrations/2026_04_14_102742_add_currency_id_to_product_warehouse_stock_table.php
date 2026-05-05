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
    Schema::table('product_warehouse_stock', function (Blueprint $table) {
      // Add currency_id - Always PEN (3) as base currency
      // All prices (cost_price, average_cost, sale_price) are stored in PEN
      $table->foreignId('currency_id')
        ->comment('Currency for all prices - always PEN (base currency)')
        ->default(3)
        ->after('sale_price')
        ->constrained('type_currency');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('product_warehouse_stock', function (Blueprint $table) {
      $table->dropForeign(['currency_id']);
      $table->dropColumn('currency_id');
    });
  }
};
