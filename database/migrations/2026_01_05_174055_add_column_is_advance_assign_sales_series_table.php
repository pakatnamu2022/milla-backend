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
    Schema::table('assign_sales_series', function (Blueprint $table) {
      $table->boolean('is_advance')->default(false)->after('type');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('assign_sales_series', function (Blueprint $table) {
      $table->dropColumn('is_advance');
    });
  }
};
