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
    Schema::table('ap_work_orders', function (Blueprint $table) {
      // Validador: límite de reversiones permitidas (se resetea con autorización)
      $table->unsignedInteger('internal_note_revert_count')->default(0)->after('output_generation_warehouse');

      // Contador histórico total de reversiones (nunca se resetea)
      $table->unsignedInteger('internal_note_total_reverts')->default(0)->after('internal_note_revert_count');

      // Usuario que autorizó la última reversión
      $table->integer('internal_note_revert_authorized_by')->nullable()->after('internal_note_total_reverts');

      // Fecha de la última autorización de reversión
      $table->timestamp('internal_note_revert_authorized_at')->nullable()->after('internal_note_revert_authorized_by');

      // Foreign key
      $table->foreign('internal_note_revert_authorized_by')->references('id')->on('usr_users')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->dropForeign(['internal_note_revert_authorized_by']);
      $table->dropColumn([
        'internal_note_revert_count',
        'internal_note_total_reverts',
        'internal_note_revert_authorized_by',
        'internal_note_revert_authorized_at',
      ]);
    });
  }
};
