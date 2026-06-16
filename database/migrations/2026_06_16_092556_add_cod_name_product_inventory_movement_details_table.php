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
    Schema::table('inventory_movement_details', function (Blueprint $table) {
      $table->string('code_product', 50)->nullable()->after('product_id');
      $table->string('name_product', 100)->nullable()->after('code_product');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('inventory_movement_details', function (Blueprint $table) {
      $table->dropColumn('code_product');
      $table->dropColumn('name_product');
    });
  }
};
