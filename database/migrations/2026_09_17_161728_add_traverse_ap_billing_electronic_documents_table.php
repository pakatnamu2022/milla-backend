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
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->boolean('has_product_traverse')->default(false)->after('re_invoice');
      $table->boolean('associate_purchase_traverse')->default(false)->after('has_product_traverse');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->dropColumn('has_product_traverse');
      $table->dropColumn('associate_purchase_traverse');
    });
  }
};
