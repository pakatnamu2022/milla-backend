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
    Schema::create('driver_travel_record', function (Blueprint $table) {
      $table->id();
      $table->unsignedInteger('dispatch_id');
      $table->unsignedInteger('driver_id');
      $table->enum('record_type', ['start', 'end', 'checkpoint']);
      $table->datetime('recorded_at');
      $table->decimal('recorded_mileage', 14, 2);
      $table->text('notes')->nullable();
      $table->string('device_id', 255)->nullable();
      $table->enum('sync_status', ['pending', 'completed'])->default('pending');
      $table->timestamps();

      //indices
      $table->index('dispatch_id');
      $table->index(['driver_id', 'recorded_at']);
      $table->unique(['dispatch_id', 'driver_id', 'record_type']);


      //referencias
      $table->foreign('dispatch_id')->references('id')->on('op_despacho');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('driver_travel_record');
  }
};
