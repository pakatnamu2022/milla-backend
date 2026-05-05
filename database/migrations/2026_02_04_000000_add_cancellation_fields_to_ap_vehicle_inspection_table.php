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
    Schema::table('ap_vehicle_inspection', function (Blueprint $table) {
      // Agregar ap_work_orders_id
      $table->foreignId('ap_work_order_id')->nullable()->after('id')->constrained('ap_work_orders')->onDelete('set null');
      // Campo para indicar si está anulado
      $table->boolean('is_cancelled')->default(false)->after('inspected_by');

      // Usuario que solicita la anulación
      $table->integer('cancellation_requested_by')->nullable()->after('is_cancelled');
      $table->foreign('cancellation_requested_by')->references('id')->on('usr_users')->onDelete('set null');

      // Usuario que confirma/aprueba la anulación
      $table->integer('cancellation_confirmed_by')->nullable()->after('cancellation_requested_by');
      $table->foreign('cancellation_confirmed_by')->references('id')->on('usr_users')->onDelete('set null');

      // Fecha en que se solicitó la anulación
      $table->timestamp('cancellation_requested_at')->nullable()->after('cancellation_confirmed_by');

      // Fecha en que se confirmó la anulación
      $table->timestamp('cancellation_confirmed_at')->nullable()->after('cancellation_requested_at');

      // Motivo de la anulación
      $table->text('cancellation_reason')->nullable()->after('cancellation_confirmed_at');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_inspection', function (Blueprint $table) {
      $table->dropForeign(['ap_work_order_id']);
      $table->dropColumn('ap_work_order_id');
      $table->dropForeign(['cancellation_requested_by']);
      $table->dropForeign(['cancellation_confirmed_by']);
      $table->dropColumn([
        'is_cancelled',
        'cancellation_requested_by',
        'cancellation_confirmed_by',
        'cancellation_requested_at',
        'cancellation_confirmed_at',
        'cancellation_reason',
      ]);
    });
  }
};
