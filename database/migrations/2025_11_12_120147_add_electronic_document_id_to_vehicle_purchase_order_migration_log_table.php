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
    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
      $table->foreignId('electronic_document_id')
        ->nullable()
        ->after('shipping_guide_id')
        ->constrained('ap_billing_electronic_documents', 'id', 'fk_log_electronic_document_id')
        ->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
      $table->dropForeign('fk_log_electronic_document_id');
      $table->dropColumn('electronic_document_id');
    });
  }
};
