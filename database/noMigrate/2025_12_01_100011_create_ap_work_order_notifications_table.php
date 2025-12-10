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
    Schema::create('ap_work_order_notifications', function (Blueprint $table) {
      $table->id();

      // Relations
      $table->foreignId('work_order_id')->comment('Orden de trabajo asociada')
        ->constrained('ap_work_orders')->onDelete('cascade');

      // Notification details
      $table->foreignId('notification_type_id')->comment('Tipo: REPUESTOS_LLEGARON, VEHICULO_LISTO, APROBACION_PENDIENTE, etc.')
        ->constrained('ap_post_venta_masters')->onDelete('cascade');

      $table->integer('recipient_user_id')->comment('Usuario destinatario');
      $table->foreign('recipient_user_id')->references('id')->on('usr_users')->onDelete('cascade');

      $table->string('title')->comment('Título de la notificación');
      $table->text('message')->comment('Mensaje de la notificación');

      // Status
      $table->boolean('is_read')->default(false)->comment('Si fue leída');
      $table->dateTime('read_at')->nullable()->comment('Cuándo fue leída');

      // Priority
      $table->foreignId('priority_id')->comment('Prioridad: BAJA, MEDIA, ALTA, URGENTE')
        ->constrained('ap_post_venta_masters')->onDelete('cascade');

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index('work_order_id');
      $table->index('recipient_user_id');
      $table->index('is_read');
      $table->index(['recipient_user_id', 'is_read']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_work_order_notifications');
  }
};
