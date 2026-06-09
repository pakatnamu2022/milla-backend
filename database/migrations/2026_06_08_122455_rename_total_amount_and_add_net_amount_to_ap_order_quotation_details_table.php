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
      $table->renameColumn('total_amount', 'total_cost');
    });

    Schema::table('ap_order_quotation_details', function (Blueprint $table) {
      $table->decimal('net_amount', 10, 2)->default(0)->after('total_cost');
      $table->decimal('tax_amount', 10, 2)->default(0)->after('net_amount');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_quotation_details', function (Blueprint $table) {
      $table->dropColumn('net_amount');
      $table->dropColumn('tax_amount');
    });

    Schema::table('ap_order_quotation_details', function (Blueprint $table) {
      $table->renameColumn('total_cost', 'total_amount');
    });
  }
};
