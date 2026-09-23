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
    Schema::table('ap_work_order_parts', function (Blueprint $table) {
      $table->decimal('tax_amount', 15, 4)->change();
    });

    Schema::table('work_order_labour', function (Blueprint $table) {
      $table->decimal('tax_amount', 15, 4)->change();
    });

    Schema::table('ap_order_quotation_details', function (Blueprint $table) {
      $table->decimal('tax_amount', 15, 4)->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_order_parts', function (Blueprint $table) {
      $table->decimal('tax_amount', 10, 2)->change();
    });

    Schema::table('work_order_labour', function (Blueprint $table) {
      $table->decimal('tax_amount', 10, 2)->change();
    });

    Schema::table('ap_order_quotation_details', function (Blueprint $table) {
      $table->decimal('tax_amount', 10, 2)->change();
    });
  }
};
