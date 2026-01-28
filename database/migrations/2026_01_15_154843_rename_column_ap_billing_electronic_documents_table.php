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
      $table->renameColumn('work_orders_id', 'work_order_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->renameColumn('work_order_id', 'work_orders_id');
    });
  }
};
