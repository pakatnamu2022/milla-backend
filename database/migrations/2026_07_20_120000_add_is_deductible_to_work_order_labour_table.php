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
    Schema::table('work_order_labour', function (Blueprint $table) {
      $table->boolean('is_deductible')->default(false)->after('tax_amount');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('work_order_labour', function (Blueprint $table) {
      $table->dropColumn('is_deductible');
    });
  }
};