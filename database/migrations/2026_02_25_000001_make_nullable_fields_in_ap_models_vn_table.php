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
    Schema::table('ap_models_vn', function (Blueprint $table) {
      // Make string fields nullable
      $table->string('power', 50)->nullable()->change();
      $table->year('model_year')->nullable()->change();
      $table->string('wheelbase', 50)->nullable()->change();
      $table->string('axles_number', 50)->nullable()->change();
      $table->string('width', 50)->nullable()->change();
      $table->string('length', 50)->nullable()->change();
      $table->string('height', 50)->nullable()->change();
      $table->string('seats_number', 50)->nullable()->change();
      $table->string('doors_number', 50)->nullable()->change();
      $table->string('net_weight', 50)->nullable()->change();
      $table->string('gross_weight', 50)->nullable()->change();
      $table->string('payload', 50)->nullable()->change();
      $table->string('displacement', 50)->nullable()->change();
      $table->string('cylinders_number', 50)->nullable()->change();
      $table->string('passengers_number', 50)->nullable()->change();
      $table->string('wheels_number', 50)->nullable()->change();

      // default 0
      $table->decimal('distributor_price', 15, 4)->default(0)->change();
      $table->decimal('transport_cost', 15, 4)->default(0)->change();
      $table->decimal('other_amounts', 15, 4)->default(0)->change();
      $table->decimal('purchase_discount', 15, 4)->default(0)->change();
      $table->decimal('igv_amount', 15, 4)->default(0)->change();
      $table->decimal('total_purchase_excl_igv', 15, 4)->default(0)->change();
      $table->decimal('total_purchase_incl_igv', 15, 4)->default(0)->change();
      $table->decimal('sale_price', 15, 4)->default(0)->change();
      $table->decimal('margin', 15, 4)->default(0)->change();

      // Make foreign key fields nullable
      $table->unsignedBigInteger('class_id')->nullable()->change();
      $table->unsignedBigInteger('fuel_id')->nullable()->change();
      $table->unsignedBigInteger('vehicle_type_id')->nullable()->change();
      $table->unsignedBigInteger('body_type_id')->nullable()->change();
      $table->unsignedBigInteger('traction_type_id')->nullable()->change();
      $table->unsignedBigInteger('transmission_id')->nullable()->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_models_vn', function (Blueprint $table) {
      // Revert string fields to NOT NULL
      $table->string('power', 50)->nullable(false)->change();
      $table->year('model_year')->nullable(false)->change();
      $table->string('wheelbase', 50)->nullable(false)->change();
      $table->string('axles_number', 50)->nullable(false)->change();
      $table->string('width', 50)->nullable(false)->change();
      $table->string('length', 50)->nullable(false)->change();
      $table->string('height', 50)->nullable(false)->change();
      $table->string('seats_number', 50)->nullable(false)->change();
      $table->string('doors_number', 50)->nullable(false)->change();
      $table->string('net_weight', 50)->nullable(false)->change();
      $table->string('gross_weight', 50)->nullable(false)->change();
      $table->string('payload', 50)->nullable(false)->change();
      $table->string('displacement', 50)->nullable(false)->change();
      $table->string('cylinders_number', 50)->nullable(false)->change();
      $table->string('passengers_number', 50)->nullable(false)->change();
      $table->string('wheels_number', 50)->nullable(false)->change();

      // remove default
      $table->decimal('distributor_price', 15, 4)->default(null)->change();
      $table->decimal('transport_cost', 15, 4)->default(null)->change();
      $table->decimal('other_amounts', 15, 4)->default(null)->change();
      $table->decimal('purchase_discount', 15, 4)->default(null)->change();
      $table->decimal('igv_amount', 15, 4)->default(null)->change();
      $table->decimal('total_purchase_excl_igv', 15, 4)->default(null)->change();
      $table->decimal('total_purchase_incl_igv', 15, 4)->default(null)->change();
      $table->decimal('sale_price', 15, 4)->default(null)->change();
      $table->decimal('margin', 15, 4)->default(null)->change();

      // Revert foreign key fields to NOT NULL
      $table->unsignedBigInteger('class_id')->nullable(false)->change();
      $table->unsignedBigInteger('fuel_id')->nullable(false)->change();
      $table->unsignedBigInteger('vehicle_type_id')->nullable(false)->change();
      $table->unsignedBigInteger('body_type_id')->nullable(false)->change();
      $table->unsignedBigInteger('traction_type_id')->nullable(false)->change();
      $table->unsignedBigInteger('transmission_id')->nullable(false)->change();
    });
  }
};

