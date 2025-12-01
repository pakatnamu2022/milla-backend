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
    Schema::table('ap_models_vn', function (Blueprint $table) {
      $table->foreignId('type_operation_id')->nullable()->after('currency_type_id')->constrained('ap_commercial_masters')->onDelete('set null');
      $table->unsignedBigInteger('currency_type_id')->nullable()->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_models_vn', function (Blueprint $table) {
      $table->dropForeign(['type_operation_id']);
      $table->dropColumn('type_operation_id');
      $table->unsignedBigInteger('currency_type_id')->change();
    });
  }
};
