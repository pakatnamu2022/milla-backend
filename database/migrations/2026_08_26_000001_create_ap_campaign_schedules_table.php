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
    Schema::create('ap_campaign_schedules', function (Blueprint $table) {
      $table->id();
      $table->integer('sede_id');
      $table->integer('worker_id');
      $table->date('date');
      $table->integer('created_by')->nullable();
      $table->timestamps();

      $table->foreign('sede_id')
        ->references('id')
        ->on('config_sede')
        ->onDelete('cascade');

      $table->foreign('worker_id')
        ->references('id')
        ->on('rrhh_persona')
        ->onDelete('cascade');

      $table->foreign('created_by')
        ->references('id')
        ->on('usr_users')
        ->onDelete('set null');

      $table->index('sede_id');
      $table->index('worker_id');
      $table->index('date');

      // Unique constraint: un técnico solo puede estar asignado a una sede en una fecha específica
      $table->unique(['worker_id', 'date'], 'unique_worker_date_schedule');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_campaign_schedules');
  }
};
