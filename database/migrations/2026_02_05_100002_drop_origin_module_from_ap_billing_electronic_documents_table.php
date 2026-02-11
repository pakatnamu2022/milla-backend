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
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->dropIndex('idx_origin_ref');
      $table->dropColumn('origin_module');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->enum('origin_module', ['comercial', 'posventa'])->nullable()->comment('Módulo de origen')->after('sunat_concept_transaction_type_id');
      $table->index(['origin_module', 'origin_entity_type', 'origin_entity_id'], 'idx_origin_ref');
    });
  }
};
