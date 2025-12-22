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
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->string('description_recall', 500)->nullable()->after('is_recall')->comment('Descripción del recall asociado a la orden de trabajo');
      $table->string('type_recall', 50)->nullable()->after('description_recall')->comment('Tipo de recall asociado a la orden de trabajo');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->dropColumn('description_recall');
      $table->dropColumn('type_recall');
    });
  }
};
