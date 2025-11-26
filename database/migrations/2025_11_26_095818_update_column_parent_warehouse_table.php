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
      $table->boolean('is_physical_warehouse')->default(false)->after('parent_warehouse_id')->comment('Indicates if the warehouse is a physical warehouse');
      $table->unsignedBigInteger('article_class_id')->nullable()->change();
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
      $table->dropColumn('is_physical_warehouse');
      $table->unsignedBigInteger('article_class_id')->change();
    });
  }
};
