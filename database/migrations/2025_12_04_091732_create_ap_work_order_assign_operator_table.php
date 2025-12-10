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
    Schema::create('ap_work_order_assign_operator', function (Blueprint $table) {
      $table->id();

      // Relations
      $table->foreignId('work_order_id')->comment('Orden de trabajo')
        ->constrained('ap_work_orders')->onDelete('cascade');

      $table->integer('group_number')->comment('Número de grupo para agrupar');

      $table->integer('operator_id')->comment('Operario de servicio responsable');
      $table->foreign('operator_id')->references('id')->on('rrhh_persona')->onDelete('cascade');

      $table->integer('registered_by')->comment('Usuario que registró');
      $table->foreign('registered_by')->references('id')->on('usr_users')->onDelete('cascade');

      $table->enum('status', ['PENDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'])
        ->default('PENDING')->comment('Estado de la asignación');

      $table->text('observations')->nullable()->comment('Observaciones');

      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_work_order_assign_operator');
  }
};
