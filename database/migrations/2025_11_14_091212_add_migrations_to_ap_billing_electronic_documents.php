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
      $table->enum('migration_status', ['pending', 'in_progress', 'completed', 'failed', 'updated_with_nc'])->default('pending')
        ->comment('Estado de la migración a la BD intermedia');
      $table->timestamp('migrated_at')->nullable()->comment('Fecha y hora en que se completó la migración');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->dropColumn('migration_status');
      $table->dropColumn('status');
    });
  }
};
