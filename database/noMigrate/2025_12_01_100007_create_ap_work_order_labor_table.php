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
    Schema::create('ap_work_order_labor', function (Blueprint $table) {
      $table->id();

      // Relations
      $table->foreignId('work_order_id')->comment('Orden de trabajo')
        ->constrained('ap_work_orders')->onDelete('cascade');

      $table->foreignId('work_order_item_id')->nullable()->comment('Ítem al que pertenece')
        ->constrained('ap_work_order_items')->onDelete('set null');

      // Labor details
      $table->foreignId('labor_type_id')->comment('Tipo: DIAGNOSTICO, REPARACION, MANTENIMIENTO, etc.')
        ->constrained('ap_post_venta_masters')->onDelete('cascade');

      $table->string('description')->comment('Descripción del trabajo de mano de obra');

      // Worker assignment
      $table->integer('worker_id')->comment('Técnico que realizó el trabajo');
      $table->foreign('worker_id')->references('id')->on('usr_users')->onDelete('cascade');

      // Time and rate
      $table->decimal('hours_worked', 8, 2)->comment('Horas trabajadas');
      $table->decimal('hourly_rate', 12, 2)->comment('Tarifa por hora');

      // Worker commission
      $table->decimal('worker_commission_percentage', 5, 2)->default(0)
        ->comment('Porcentaje de comisión para el trabajador');
      $table->decimal('worker_commission_amount', 12, 2)->default(0)
        ->comment('Monto de comisión del trabajador');

      // Pricing
      $table->decimal('subtotal', 12, 2)->comment('Subtotal (hours * rate)');
      $table->decimal('discount_percentage', 5, 2)->default(0)->comment('Descuento %');
      $table->decimal('tax_amount', 12, 2)->default(0)->comment('Impuestos (IGV)');
      $table->decimal('total_amount', 12, 2)->comment('Total con impuestos');

      // Work timing
      $table->date('work_date')->comment('Fecha en que se realizó');
      $table->time('start_time')->nullable()->comment('Hora de inicio');
      $table->time('end_time')->nullable()->comment('Hora de fin');

      // Flags
      $table->boolean('is_warranty')->default(false)->comment('Si es trabajo de garantía');

      // Notes
      $table->text('observations')->nullable()->comment('Observaciones');

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index('work_order_id');
      $table->index('worker_id');
      $table->index('work_date');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_work_order_labor');
  }
};
