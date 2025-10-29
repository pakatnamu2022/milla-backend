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
    Schema::create('ap_purchase_order', function (Blueprint $table) {
      $table->id();
      $table->string('number');
      $table->integer('number_correlative')->comment('Número correlativo de la OC para casos de corrección por NC');

//      GUIDE
      $table->string('number_guide');
      $table->foreignId('warehouse_id')->constrained('warehouse');

//      INVOICE
      $table->string('invoice_series')->comment('Serie de la factura');
      $table->string('invoice_number')->comment('Número de la factura');
      $table->date('emission_date')->comment('Fecha de emisión de la factura');
      $table->date('due_date')->nullable()->comment('Fecha de vencimiento de la factura');
//      $table->decimal('unit_price')->comment('Precio unitario del vehículo sin descuentos ni impuestos');
      $table->decimal('discount')->default(0)->comment('Descuento aplicado en la factura');
      $table->decimal('subtotal')->comment('Subtotal aplicado en la factura');
      $table->decimal('isc')->default(0);
      $table->decimal('igv')->default(0);
      $table->decimal('total');
      $table->foreignId('supplier_id')->constrained('business_partners');
      $table->foreignId('currency_id')->constrained('type_currency');
      $table->foreignId('exchange_rate_id')->constrained('ap_commercial_masters');
      $table->foreignId('supplier_order_type_id')
        ->constrained('ap_commercial_masters')->onDelete('cascade');

      $table->integer('sede_id');
      $table->foreign('sede_id')->references('id')->on('config_sede');

//      STATUS
      $table->string('invoice_dynamics')->nullable()->comment('Número de factura en el sistema Dynamics');
      $table->string('receipt_dynamics')->nullable()->comment('Número de recibo en el sistema Dynamics');
      $table->string('credit_note_dynamics')->nullable()->comment('Número de nota de crédito en el sistema Dynamics');
      $table->boolean('resent')->default(false)->comment('Indica si la OC anulada ya fue reenviada (true=ya reenviada, false=no reenviada)');
      $table->unsignedBigInteger('original_purchase_order_id')->nullable()->comment('ID de la OC original cuando esta es una corrección por NC');
      $table->foreign('original_purchase_order_id')->references('id')->on('ap_vehicle_purchase_order')->onDelete('set null');
      $table->enum('migration_status', ['pending', 'in_progress', 'completed', 'failed', 'updated_with_nc'])->default('pending')->comment('Estado de la migración a la BD intermedia');
      $table->boolean('status')->default(true)->comment('Estado de la OC: true=activa, false=anulada (con NC)');

      $table->foreignId('vehicle_movement_id')->nullable()->constrained('ap_vehicle_movement')->onDelete('cascade');

//      TIMESTAMPS
      $table->timestamp('migrated_at')->nullable()->comment('Fecha y hora en que se completó la migración');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_purchase_order');
  }
};
