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
      $table->boolean('was_dyn_requested')->default(false)
        ->comment('Indica si se solicitó migración a Dynamics 365')
        ->after('migration_status');
      $table->boolean('is_accounted')->nullable()->default(null)
        ->comment('Indica si el documento está contabilizado en Dynamics 365 (encontrado en SOP30200)')
        ->after('was_dyn_requested');
      $table->boolean('is_annulled')->nullable()->default(null)
        ->comment('Indica si el documento está anulado en Dynamics 365 (VOIDSTTS = 1)')
        ->after('is_accounted');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->dropColumn(['was_dyn_requested', 'is_accounted', 'is_annulled']);
    });
  }
};
