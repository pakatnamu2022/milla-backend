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
    Schema::create('ap_work_orders', function (Blueprint $table) {
      $table->id();

      // Correlative and basic info
      $table->string('correlative', 50)->unique()->comment('Código único autogenerado (ej: OT-2025-0001)');

      // Relations
      $table->foreignId('appointment_planning_id')->nullable()->comment('Cita asociada si aplica')
        ->constrained('appointment_planning')->onDelete('set null');

      $table->foreignId('vehicle_id')->comment('Vehículo en servicio')
        ->constrained('ap_vehicles')->onDelete('cascade');

      // Vehicle info for quick search
      $table->string('vehicle_plate', 20)->nullable()->comment('Placa del vehículo');
      $table->string('vehicle_vin', 50)->nullable()->comment('VIN del vehículo');

      $table->foreignId('status_id')->comment('Estado: ABIERTA, EN_PROCESO, COMPLETADA, etc.')
        ->constrained('ap_masters')->onDelete('cascade');

      // Responsible users
      $table->integer('advisor_id')->comment('Asesor de servicio responsable');
      $table->foreign('advisor_id')->references('id')->on('rrhh_persona')->onDelete('cascade');

      $table->integer('sede_id')->comment('Sede donde se realiza el servicio');
      $table->foreign('sede_id')->references('id')->on('config_sede')->onDelete('cascade');

      // Dates
      $table->dateTime('opening_date')->comment('Fecha y hora de apertura de la orden');
      $table->dateTime('estimated_delivery_date')->nullable()->comment('Fecha estimada de entrega');
      $table->dateTime('actual_delivery_date')->nullable()->comment('Fecha real de entrega');
      $table->dateTime('diagnosis_date')->nullable()->comment('Fecha de diagnóstico del vehículo');

      // Input
      $table->text('observations')->nullable()->comment('Observaciones');

      // Financial calculations
      $table->decimal('total_labor_cost', 12, 2)->default(0)->comment('Total mano de obra');
      $table->decimal('total_parts_cost', 12, 2)->default(0)->comment('Total repuestos');
      $table->decimal('subtotal', 12, 2)->default(0)->comment('Subtotal antes de descuentos e impuestos');
      $table->decimal('discount_percentage', 5, 2)->default(0)->comment('Porcentaje de descuento');
      $table->decimal('discount_amount', 12, 2)->default(0)->comment('Monto de descuento');
      $table->decimal('tax_amount', 12, 2)->default(0)->comment('Impuestos (IGV)');
      $table->decimal('final_amount', 12, 2)->default(0)->comment('Monto final con descuentos e impuestos');

      // Flags
      $table->boolean('is_invoiced')->default(false)->comment('Si ya fue facturado');

      // Audit
      $table->integer('created_by')->comment('Usuario que creó la orden');
      $table->foreign('created_by')->references('id')->on('usr_users')->onDelete('cascade');

      $table->timestamps();
      $table->softDeletes();

      // Indexes for better performance
      $table->index('correlative');
      $table->index('vehicle_plate');
      $table->index('vehicle_vin');
      $table->index('status_id');
      $table->index('opening_date');
      $table->index('sede_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_work_orders');
  }
};
