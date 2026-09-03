<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('ap_assets', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('ap_vehicle_id');
      $table->unsignedBigInteger('worker_id');
      $table->date('assigned_date');
      $table->text('observation')->nullable();
      $table->string('dyn_series', 50)->nullable()
        ->comment('TransaccionId enviado a Dynamics (neInTbTransaccionInventario)');
      $table->enum('migration_status', [
        'pending',
        'in_progress',
        'completed',
        'failed',
        'skipped',
      ])->default('pending');
      $table->unsignedBigInteger('created_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('ap_vehicle_id')->references('id')->on('ap_vehicles')->onDelete('cascade');
      $table->foreign('worker_id')->references('id')->on('rrhh_persona')->onDelete('restrict');
      $table->foreign('created_by')->references('id')->on('usr_users')->onDelete('set null');

      $table->index('ap_vehicle_id');
      $table->index('worker_id');
      $table->index('migration_status');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_assets');
  }
};
