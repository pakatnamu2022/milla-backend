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
      $table->string('customer_signature_delivery_url')->nullable()->after('customer_signature_url');
      $table->string('delivery_document_number')->nullable()->after('customer_signature_delivery_url');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->dropColumn('customer_signature_delivery_url');
      $table->dropColumn('delivery_document_number');
    });
  }
};
