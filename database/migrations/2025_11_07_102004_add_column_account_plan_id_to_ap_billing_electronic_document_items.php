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
      $table->foreignId('account_plan_id')->default(6)
        ->after('ap_billing_electronic_document_id')
        ->constrained('ap_accounting_account_plan', 'id', 'fk_ap_electronic_document_items_account_plan');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
      $table->dropForeign('fk_ap_electronic_document_items_account_plan');
      $table->dropColumn('account_plan_id');
    });
  }
};
