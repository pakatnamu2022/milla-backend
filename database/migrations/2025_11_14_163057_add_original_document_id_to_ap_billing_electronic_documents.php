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
      $table->foreignId('original_document_id')
        ->after('documento_que_se_modifica_numero')
        ->nullable()
        ->constrained('ap_billing_electronic_documents');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->dropForeign(['original_document_id']);
      $table->dropColumn('original_document_id');
    });
  }
};
