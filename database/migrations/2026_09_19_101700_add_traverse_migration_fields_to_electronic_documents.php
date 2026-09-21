<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->enum('traverse_migration_status', ['pending', 'in_progress', 'completed', 'failed', 'reverted'])
        ->nullable()
        ->after('associate_purchase_traverse')
        ->comment('Estado de migración a Dynamics de travesía');

      $table->timestamp('traverse_migrated_at')
        ->nullable()
        ->after('traverse_migration_status')
        ->comment('Fecha de migración completada de travesía');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->dropColumn(['traverse_migration_status', 'traverse_migrated_at']);
    });
  }
};