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
    Schema::create('ap_models_vn', function (Blueprint $table) {
      $table->id();
      $table->string('code', length: 50);
      $table->string('version', length: 255);
      $table->string('power', length: 50);
      $table->year('model_year');
      $table->string('wheelbase', length: 50);
      $table->string('axles_number', length: 50);
      $table->string('width', length: 50);
      $table->string('length', length: 50);
      $table->string('height', length: 50);
      $table->string('seats_number', length: 50);
      $table->string('doors_number', length: 50);
      $table->string('net_weight', length: 50);
      $table->string('gross_weight', length: 50);
      $table->string('payload', length: 50);
      $table->string('displacement', length: 50);
      $table->string('cylinders_number', length: 50);
      $table->string('passengers_number', length: 50);
      $table->string('wheels_number', length: 50);
      $table->decimal('distributor_price', 10, 4);
      $table->decimal('transport_cost', 10, 4);
      $table->decimal('other_amounts', 10, 4);
      $table->decimal('purchase_discount', 10, 4);
      $table->decimal('igv_amount', 10, 4);
      $table->decimal('total_purchase_excl_igv', 10, 4);
      $table->decimal('total_purchase_incl_igv', 10, 4);
      $table->decimal('sale_price', 10, 4);
      $table->decimal('margin', 10, 4);
      $table->boolean('status')->default(true);
      $table->foreignId('family_id')
        ->constrained('ap_families')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreignId('class_id')
        ->constrained('ap_class_article')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreignId('fuel_id')
        ->constrained('ap_fuel_type')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreignId('vehicle_type_id')
        ->constrained('ap_commercial_masters')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreignId('body_type_id')
        ->constrained('ap_commercial_masters')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreignId('traction_type_id')
        ->constrained('ap_commercial_masters')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreignId('transmission_id')
        ->constrained('ap_commercial_masters')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreignId('currency_type_id')
        ->constrained('type_currency')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_models_vn');
  }
};
