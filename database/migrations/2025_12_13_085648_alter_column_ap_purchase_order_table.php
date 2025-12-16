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
    Schema::table('ap_purchase_order', function (Blueprint $table) {
      $table->unsignedBigInteger('supplier_order_type_id')->nullable()->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_purchase_order', function (Blueprint $table) {
      $table->unsignedBigInteger('supplier_order_type_id')->change();
    });
  }
};
