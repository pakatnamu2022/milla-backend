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
    Schema::table('type_planning_work_order', function (Blueprint $table) {
      $table->enum('category_type', ['ESTANDAR', 'INTERNA', 'GARANTIA_RECALL'])->default('ESTANDAR')->after('type_document')->comment('ESTANDAR, INTERNA, GARANTIA_RECALL');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('type_planning_work_order', function (Blueprint $table) {
      $table->dropColumn('category_type');
    });
  }
};
