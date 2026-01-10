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
    Schema::create('ap_vehicle_purchase_order', function (Blueprint $table) {
      $table->id();

      //      VEHICLE
      $table->string('vin');
      $table->integer('year');
      $table->string('engine_number');
      $table->foreignId('ap_models_vn_id')
        ->constrained('ap_models_vn')->onDelete('cascade');
      $table->foreignId('vehicle_color_id')
        ->constrained('ap_masters')->onDelete('cascade');
      $table->foreignId('supplier_order_type_id')
        ->constrained('ap_masters')->onDelete('cascade');
      $table->foreignId('engine_type_id')
        ->constrained('ap_masters')->onDelete('cascade');
      $table->foreignId('ap_vehicle_status_id')
        ->constrained('ap_vehicle_status')->onDelete('cascade');
      $table->integer('sede_id');
      $table->foreign('sede_id')->references('id')->on('config_sede');

      //      INVOICE
      $table->string('invoice_series');
      $table->string('invoice_number');
      $table->date('emission_date');
      $table->decimal('unit_price');
      $table->decimal('discount')->default(0);
      $table->decimal('subtotal');
      $table->decimal('igv')->default(0);
      $table->decimal('total');
      $table->foreignId('supplier_id')->constrained('business_partners');
      $table->foreignId('currency_id')->constrained('type_currency');
      $table->foreignId('exchange_rate_id')->constrained('ap_masters');

      //      GUIDE
      $table->string('number');
      $table->string('number_guide');
      $table->foreignId('warehouse_id')->constrained('warehouse');
      $table->foreignId('warehouse_physical_id')->nullable()->constrained('warehouse');

      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_vehicle_purchase_order');
  }
};
