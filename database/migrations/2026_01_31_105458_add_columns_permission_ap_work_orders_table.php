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
      $table->boolean('allow_remove_associated_quote')->default(false)->after('has_invoice_generated')->comment('Indica si se permite eliminar la cotización asociada a la orden de trabajo');
      $table->boolean('allow_editing_inspection')->default(false)->after('allow_remove_associated_quote')->comment('Indica si se permite editar la inspección del vehículo asociada a la orden de trabajo');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->dropColumn('allow_remove_associated_quote');
      $table->dropColumn('allow_editing_inspection');
    });
  }
};
