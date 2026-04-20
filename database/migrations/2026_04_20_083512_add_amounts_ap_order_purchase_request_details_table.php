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
    Schema::table('ap_order_purchase_request_details', function (Blueprint $table) {
      $table->decimal('unit_price', 12)->default(0)->after('quantity');
      $table->decimal('discount_percentage', 12)->default(0)->after('unit_price');
      $table->decimal('total_amount', 12)->default(0)->after('unit_price');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_purchase_request_details', function (Blueprint $table) {
      $table->dropColumn('unit_price');
      $table->dropColumn('discount_percentage');
      $table->dropColumn('total_amount');
    });
  }
};
