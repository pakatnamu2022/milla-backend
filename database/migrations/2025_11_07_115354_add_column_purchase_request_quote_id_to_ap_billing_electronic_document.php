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
      $table->foreignId('purchase_request_quote_id')
        ->nullable()->after('client_id')
        ->constrained('purchase_request_quote', 'id', 'fk_ap_electro_doc_request_quote');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->dropForeign('fk_ap_electro_doc_request_quote');
      $table->dropColumn('purchase_request_quote_id');
    });
  }
};
