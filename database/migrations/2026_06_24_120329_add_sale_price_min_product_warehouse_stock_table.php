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
      $table->decimal('sale_price_min', 10)->default(0)->after('sale_price');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('product_warehouse_stock', function (Blueprint $table) {
      $table->dropColumn('sale_price_min');
    });
  }
};
