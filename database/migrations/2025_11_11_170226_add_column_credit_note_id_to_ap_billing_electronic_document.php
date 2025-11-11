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
      $table->foreignId('credit_note_id')
        ->nullable()->after('purchase_request_quote_id')
        ->constrained('ap_billing_electronic_documents', 'id', 'fk_electro_doc_credit_note');
      $table->foreignId('debit_note_id')
        ->nullable()->after('credit_note_id')
        ->constrained('ap_billing_electronic_documents', 'id', 'fk_electro_doc_debit_note');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->dropForeign('fk_electro_doc_credit_note');
      $table->dropColumn('credit_note_id');
      $table->dropForeign('fk_electro_doc_debit_note');
      $table->dropColumn('debit_note_id');
    });
  }
};
