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
    Schema::create('ap_order_quotations', function (Blueprint $table) {
      $table->id();

      // Relations
      $table->foreignId('vehicle_id')->comment('Vehículo asociado')
        ->constrained('ap_vehicles')->onDelete('cascade');

      // Quotation info
      $table->string('quotation_number', 50)->unique()->comment('Número único de cotización (ej: COT-OT-2025-0001)');

      // Financial calculations
      $table->decimal('subtotal', 12, 2)->default(0)->comment('Subtotal sin descuentos ni impuestos');
      $table->decimal('discount_percentage', 5, 2)->default(0)->comment('Porcentaje de descuento');
      $table->decimal('discount_amount', 12, 2)->default(0)->comment('Monto de descuento');
      $table->decimal('tax_amount', 12, 2)->default(0)->comment('Impuestos (IGV)');
      $table->decimal('total_amount', 12, 2)->default(0)->comment('Monto total');

      // Validity and dates
      $table->integer('validity_days')->default(15)->comment('Días de validez de la cotización');
      $table->date('quotation_date')->comment('Fecha de la cotización');
      $table->date('expiration_date')->comment('Fecha de vencimiento');

      // Notes
      $table->text('observations')->nullable()->comment('Observaciones generales');

      // Audit
      $table->integer('created_by')->comment('Usuario que creó la cotización');
      $table->foreign('created_by')->references('id')->on('usr_users')->onDelete('cascade');

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index('vehicle_id');
      $table->index('quotation_number');
      $table->index('quotation_date');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_order_quotations');
  }
};
