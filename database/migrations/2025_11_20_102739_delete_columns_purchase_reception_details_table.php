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
    Schema::table('purchase_reception_details', function (Blueprint $table) {
      $table->dropColumn('quantity_accepted');
      $table->dropColumn('is_charged');
      $table->dropColumn('unit_cost');
      $table->dropColumn('total_cost');
      // Renombrar columnas de rejection a observation
      $table->renameColumn('quantity_rejected', 'observed_quantity');
      $table->renameColumn('rejection_reason', 'reason_observation');
      $table->renameColumn('rejection_notes', 'observation_notes');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('purchase_reception_details', function (Blueprint $table) {
      $table->decimal('quantity_accepted', 10, 2)->after('quantity_received')->comment('Quantity accepted for inventory');
      $table->boolean('is_charged')->default(false)->comment('Indicates if the item cost is charged to the purchase order');
      $table->decimal('unit_cost', 10, 2)->default(0)->comment('Unit cost of the received item');
      $table->decimal('total_cost', 10, 2)->default(0)->comment('Total cost of the received item (unit cost x quantity accepted)');
      // Revertir los cambios
      $table->renameColumn('observed_quantity', 'quantity_rejected');
      $table->renameColumn('reason_observation', 'rejection_reason');
      $table->renameColumn('observation_notes', 'rejection_notes');
    });
  }
};
