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
    Schema::table('ap_vehicle_brand', function (Blueprint $table) {
      $table->dropColumn('is_commercial');
      $table->foreignId('type_operation_id')->nullable()->after('logo_min')->constrained('ap_commercial_masters')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_brand', function (Blueprint $table) {
      $table->boolean('is_commercial')->default(true)->after('description');
      $table->dropForeign(['type_operation_id']);
      $table->dropColumn('type_operation_id');
    });
  }
};
