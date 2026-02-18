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
      $table->foreignId('quotation_id')->nullable()->after('vehicle_movement_id')
        ->constrained('purchase_request_quote')
        ->comment('Cotización asociada (opcional)');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_purchase_order', function (Blueprint $table) {
      $table->dropForeign(['quotation_id']);
      $table->dropColumn('quotation_id');
    });
  }
};
