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
    Schema::table('type_currency', function (Blueprint $table) {
      $table->boolean('enable_commercial')->default(false)->after('status');
      $table->boolean('enable_after_sales')->default(false)->after('enable_commercial');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('type_currency', function (Blueprint $table) {
      $table->dropColumn('enable_commercial');
      $table->dropColumn('enable_after_sales');
    });
  }
};
