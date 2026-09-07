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
    Schema::table('inventory_movements', function (Blueprint $table) {
      $table->boolean('is_ignored')->default(false)->after('status')->comment('Indica si el movimiento debe ser ignorado en cálculos de stock y costos');
      $table->timestamp('ignored_at')->nullable()->after('is_ignored')->comment('Fecha y hora en que se ignoró el movimiento');
      $table->integer('ignored_by')->nullable()->after('ignored_at')->comment('ID del usuario que ignoró el movimiento');
      $table->text('ignore_reason')->nullable()->after('ignored_by')->comment('Razón por la cual se ignoró el movimiento');

      // Foreign key
      $table->foreign('ignored_by')->references('id')->on('usr_users')->onDelete('set null');

      // Index para mejorar consultas
      $table->index('is_ignored');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('inventory_movements', function (Blueprint $table) {
      $table->dropForeign(['ignored_by']);
      $table->dropIndex(['is_ignored']);
      $table->dropColumn(['is_ignored', 'ignored_at', 'ignored_by', 'ignore_reason']);
    });
  }
};
