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
    Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
      $table->unsignedBigInteger('product_id')->nullable()->after('account_plan_id');
      $table->boolean('is_traverse')->default(false)->after('product_id');

      $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
      $table->dropForeign(['product_id']);
      $table->dropColumn('product_id');
      $table->dropColumn('is_traverse');
    });
  }
};
