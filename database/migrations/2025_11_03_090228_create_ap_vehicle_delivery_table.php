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
    Schema::create('ap_vehicle_delivery', function (Blueprint $table) {
      $table->id();
      $table->integer('advisor_id');
      $table->foreign('advisor_id')->references('id')->on('rrhh_persona');
      $table->foreignId('vehicle_id')->constrained('ap_vehicles')->onDelete('cascade');
      $table->date('scheduled_delivery_date')->nullable();
      $table->date('wash_date')->nullable();
      $table->date('actual_delivery_date')->nullable();
      $table->text('observations')->nullable();
      $table->boolean('status_nubefact')->default(false)->comment('Indica si la entrega ha sido sincronizada con Nubefact');
      $table->boolean('status_sunat')->default(false)->comment('Indica si la entrega ha sido sincronizada con Sunat');
      $table->boolean('status_dynamic')->default(false)->comment('Indica si la entrega ha sido sincronizada con Dynamic');
      $table->boolean('status')->default(true)->comment('Estado de la entrega: true=activo, false=inactivo');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_vehicle_delivery');
  }
};
