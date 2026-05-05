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
      $table->string('card_last4', 4)->nullable()->after('updated_by');
      $table->string('internal_note', 255)->nullable()->after('card_last4');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->dropColumn('card_last4');
      $table->dropColumn('internal_note');
    });
  }
};
