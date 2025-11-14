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
      $table->foreignId('series_id')
        ->after('sunat_concept_document_type_id')
        ->default(11)
        ->constrained('assign_sales_series', 'id', 'fk_electronic_documents_series');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->dropForeign(['fk_electronic_documents_series']);
      $table->dropColumn('series_id');
    });
  }
};
