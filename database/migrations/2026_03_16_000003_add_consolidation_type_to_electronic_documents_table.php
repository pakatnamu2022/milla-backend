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
      $table->string('consolidation_type', 50)->nullable()->after('internal_note')->default('simple')
        ->comment('Consolidation type: Agrupamiento de OT, etc.');

      // Index to filter consolidated invoices
      $table->index('consolidation_type');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->dropIndex(['consolidation_type']);
      $table->dropColumn('consolidation_type');
    });
  }
};
