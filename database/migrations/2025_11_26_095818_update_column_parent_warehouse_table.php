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
    Schema::table('warehouse', function (Blueprint $table) {
      $table->foreignId('parent_warehouse_id')->nullable()->constrained('warehouse')->nullOnDelete()->after('id')->comment('Parent warehouse for sub-warehouses');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('warehouse', function (Blueprint $table) {
      $table->dropForeign(['parent_warehouse_id']);
      $table->dropColumn('parent_warehouse_id');
    });
  }
};
