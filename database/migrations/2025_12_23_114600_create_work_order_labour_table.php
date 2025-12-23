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
    Schema::create('work_order_labour', function (Blueprint $table) {
      $table->id();
      $table->string('description')->comment('Descripción del trabajo realizado');
      $table->time('time_spent')->comment('Tiempo dedicado al trabajo');
      $table->decimal('hourly_rate', 10, 2)->comment('Tarifa por hora del trabajo');
      $table->decimal('total_cost', 10, 2)->comment('Costo total del trabajo');
      $table->integer('worker_id')->comment('ID del trabajador que realizó el trabajo');
      $table->foreign('worker_id')->references('id')->on('rrhh_persona')->onDelete('cascade');
      $table->foreignId('work_order_id')->constrained('ap_work_orders')->onDelete('cascade');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('work_order_labour');
  }
};
