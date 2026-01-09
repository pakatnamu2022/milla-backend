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
      $table->foreignId('order_quotation_id')->nullable()->after('purchase_request_quote_id')->constrained('ap_order_quotations')->onDelete('set null');
      $table->foreignId('work_orders_id')->nullable()->after('order_quotation_id')->constrained('ap_work_orders')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->dropForeign(['order_quotation_id']);
      $table->dropColumn('order_quotation_id');
      $table->dropForeign(['work_orders_id']);
      $table->dropColumn('work_orders_id');
    });
  }
};
