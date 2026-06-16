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
      $table->unsignedBigInteger('shipping_guide_id')->nullable()->after('parent_quotation_id');
      $table->foreign('shipping_guide_id')->references('id')->on('shipping_guides')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->dropForeign(['shipping_guide_id']);
      $table->dropColumn('shipping_guide_id');
    });
  }
};
