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
    Schema::create('work_order_vehicle_inspection', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('work_order_id');
      $table->unsignedBigInteger('vehicle_inspection_id');
      $table->boolean('is_cancelled')->default(false);
      $table->integer('cancellation_requested_by')->nullable();
      $table->integer('cancellation_confirmed_by')->nullable();
      $table->timestamp('cancellation_requested_at')->nullable();
      $table->timestamp('cancellation_confirmed_at')->nullable();
      $table->text('cancellation_reason')->nullable();
      $table->timestamps();
      $table->softDeletes();

      // Foreign keys
      $table->foreign('work_order_id')
        ->references('id')
        ->on('ap_work_orders')
        ->onDelete('cascade');

      $table->foreign('vehicle_inspection_id')
        ->references('id')
        ->on('ap_vehicle_inspection')
        ->onDelete('cascade');

      $table->foreign('cancellation_requested_by')
        ->references('id')
        ->on('usr_users')
        ->onDelete('set null');

      $table->foreign('cancellation_confirmed_by')
        ->references('id')
        ->on('usr_users')
        ->onDelete('set null');

      // Indexes
      $table->index('work_order_id');
      $table->index('vehicle_inspection_id');
      $table->index('is_cancelled');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('work_order_vehicle_inspection');
  }
};
