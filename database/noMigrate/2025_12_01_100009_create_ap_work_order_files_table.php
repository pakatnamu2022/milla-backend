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
    Schema::create('ap_work_order_files', function (Blueprint $table) {
      $table->id();

      // Relations
      $table->foreignId('work_order_id')->comment('Orden de trabajo asociada')
        ->constrained('ap_work_orders')->onDelete('cascade');

      $table->foreignId('vehicle_inspection_id')->nullable()->comment('Inspección si es foto de inspección')
        ->constrained('ap_vehicle_inspection')->onDelete('set null');

      $table->foreignId('work_order_item_id')->nullable()->comment('Ítem si pertenece a uno específico')
        ->constrained('ap_work_order_items')->onDelete('set null');

      // File classification
      $table->foreignId('file_type_id')->comment('Tipo: FOTO_INSPECCION, FOTO_REPARACION, DOCUMENTO, FIRMA, etc.')
        ->constrained('ap_post_venta_masters')->onDelete('cascade');

      // File details
      $table->string('file_name')->comment('Nombre del archivo');
      $table->string('file_path')->comment('Ruta del archivo en el servidor');
      $table->string('file_url')->comment('URL pública del archivo');
      $table->string('mime_type', 100)->comment('Tipo MIME del archivo');
      $table->bigInteger('file_size')->comment('Tamaño del archivo en bytes');

      // Description
      $table->text('description')->nullable()->comment('Descripción del archivo');

      // Audit
      $table->integer('uploaded_by')->comment('Usuario que subió el archivo');
      $table->foreign('uploaded_by')->references('id')->on('usr_users')->onDelete('cascade');

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index('work_order_id');
      $table->index('file_type_id');
      $table->index('vehicle_inspection_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_work_order_files');
  }
};
