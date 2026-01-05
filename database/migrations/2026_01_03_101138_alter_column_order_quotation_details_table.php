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
    Schema::table('ap_order_quotation_details', function (Blueprint $table) {
      $table->renameColumn('percentage_flete_external', 'freight_commission');
      $table->renameColumn('flete_external', 'exchange_rate');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_quotation_details', function (Blueprint $table) {
      $table->renameColumn('freight_commission', 'percentage_flete_external');
      $table->renameColumn('exchange_rate', 'flete_external');
    });
  }
};
