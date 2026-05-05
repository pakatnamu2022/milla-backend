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
    Schema::table('ap_work_order_items', function (Blueprint $table) {
      // Eliminar la foreign key antigua que apuntaba a ap_masters
      $table->dropForeign('ap_work_order_items_type_planning_id_foreign');

      // Crear la nueva foreign key que apunta a type_planning_work_order
      $table->foreign('type_planning_id')
        ->references('id')
        ->on('type_planning_work_order')
        ->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_order_items', function (Blueprint $table) {
      // Eliminar la foreign key que apunta a type_planning_work_order
      $table->dropForeign('ap_work_order_items_type_planning_id_foreign');

      // Restaurar la foreign key original que apuntaba a ap_masters
      $table->foreign('type_planning_id')
        ->references('id')
        ->on('ap_masters')
        ->onDelete('cascade');
    });
  }
};
