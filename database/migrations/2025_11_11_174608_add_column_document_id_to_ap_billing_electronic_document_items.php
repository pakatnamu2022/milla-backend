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
    Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
      $table->foreignId('reference_document_id')->nullable()->after('ap_billing_electronic_document_id')->comment('ID del documento electrónico de facturación al que pertenece el ítem')
        ->constrained('ap_billing_electronic_documents', 'id', 'fk_ref_document_id_ap_b_electro_doc_items');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
      $table->dropForeign('fk_ref_document_id_ap_b_electro_doc_items');
      $table->dropColumn('reference_document_id');
    });
  }
};
