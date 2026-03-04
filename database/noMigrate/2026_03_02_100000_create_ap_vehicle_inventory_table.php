<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('ap_vehicle_inventory', function (Blueprint $table) {
      $table->id();

      $table->foreignId('ap_vehicle_id')
        ->nullable()
        ->constrained('ap_vehicles')
        ->onDelete('set null');

      // Warehouse donde el inventario dice que está el vehículo
      $table->foreignId('inventory_warehouse_id')
        ->nullable()
        ->constrained('warehouse')
        ->onDelete('set null');

      // Datos del Excel
      $table->string('vin', 17);
      $table->foreignId('vehicle_color_id')
        ->nullable()
        ->constrained('ap_masters')
        ->onDelete('set null');
      $table->foreignId('brand_id')
        ->nullable()
        ->constrained('ap_vehicle_brand')
        ->onDelete('set null');
      $table->foreignId('model_id')
        ->nullable()
        ->constrained('ap_models_vn')
        ->onDelete('set null');
      $table->integer('year')->nullable();
      $table->foreignId('fuel_type_id')
        ->nullable()
        ->constrained('ap_fuel_type')
        ->onDelete('set null');

      $table->date('adjudication_date')->nullable();
      $table->integer('days')->nullable();
      $table->date('limit_date')->nullable();
      $table->date('reception_date')->nullable();

      // Control de inventario
      $table->boolean('is_location_confirmed')->default(false)
        ->comment('Si el vehículo está donde el inventario dice que debería estar');
      $table->boolean('is_evaluated')->default(false)
        ->comment('Si el registro ya fue evaluado');
      $table->timestamp('evaluated_at')->nullable();
      $table->integer('evaluated_by')->nullable();
      $table->foreign('evaluated_by')
        ->references('id')
        ->on('usr_users')
        ->onDelete('set null');

      $table->boolean('status')->default(true);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_vehicle_inventory');
  }
};
