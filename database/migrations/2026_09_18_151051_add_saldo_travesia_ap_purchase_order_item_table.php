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
    Schema::table('ap_purchase_order_item', function (Blueprint $table) {
      $table->decimal('quantity_available_traverse', 10)->default(0)->after('quantity_pending');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_purchase_order_item', function (Blueprint $table) {
      $table->dropColumn('quantity_available_traverse');
    });
  }
};
