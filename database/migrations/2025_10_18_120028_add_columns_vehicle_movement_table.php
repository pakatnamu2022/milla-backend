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
    Schema::table('ap_vehicle_movement', function (Blueprint $table) {
      $table->string('movement_type', 50)->after('id');
      // Dirección Origen-Destino
      $table->string('origin_address')->nullable()->after('movement_date');
      $table->string('destination_address')->nullable()->after('origin_address');
      // Cancelación
      $table->text('cancellation_reason')->nullable()->after('destination_address');
      $table->integer('cancelled_by')->after('cancellation_reason')->nullable();
      $table->foreign('cancelled_by')
        ->references('id')
        ->on('usr_users');
      $table->datetime('cancelled_at')->nullable()->after('cancelled_by');
      // Status changes
      $table->foreignId('previous_status_id')->nullable()->after('cancelled_at')
        ->constrained('ap_vehicle_status');
      $table->foreignId('new_status_id')->nullable()->after('previous_status_id')
        ->constrained('ap_vehicle_status');
      $table->integer('created_by')->after('new_status_id')->nullable();
      $table->foreign('created_by')
        ->references('id')
        ->on('usr_users');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_movement', function (Blueprint $table) {
      $table->dropColumn([
        'movement_type',
        'origin_address',
        'destination_address',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'previous_status_id',
        'new_status_id',
        'created_by'
      ]);
    });
  }
};
