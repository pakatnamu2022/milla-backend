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
    Schema::create('ap_vehicle_inspection', function (Blueprint $table) {
      $table->id();

      // Relations
      $table->foreignId('work_order_id')->comment('Orden de trabajo asociada')
        ->constrained('ap_work_orders')->onDelete('cascade');

      //Kilometraje de ingreso
      $table->integer('mileage')->nullable()->comment('Kilometraje del vehículo al ingreso');

      // Fuel level
      $table->string('fuel_level')->comment('Nivel de combustible: 0, 1/4, 2/4, 3/4, 4/4');

      // Nivel de aceite
      $table->string('oil_level')->comment('Nivel de aceite: 0, 1/4, 2/4, 3/4, 4/4');

      // Damage flags
      $table->boolean('dirty_unit')->default(false)->comment('Unidad sucia');
      $table->boolean('unit_ok')->default(false)->comment('Unidad en buen estado');
      $table->boolean('title_deed')->default(false)->comment('Tiene titulo de propiedad');
      $table->boolean('soat')->default(false)->comment('Tiene SOAT');
      $table->boolean('moon_permits')->default(false)->comment('Tiene permisos de lunas');
      $table->boolean('service_card')->default(true)->comment('Carnet de servicio');
      $table->boolean('owner_manual')->default(true)->comment('Manual del propietario');
      $table->boolean('key_ring')->default(true)->comment('Llavero');
      $table->boolean('wheel_lock')->default(true)->comment('Seguro de ruedas');
      $table->boolean('safe_glasses')->default(true)->comment('Seguro de vasos');
      $table->boolean('radio_mask')->default(true)->comment('Máscara de radio');
      $table->boolean('lighter')->default(true)->comment('Encendedor');
      $table->boolean('floors')->default(true)->comment('Pisos');
      $table->boolean('seat_cover')->default(true)->comment('Funda Asiento');
      $table->boolean('quills')->default(true)->comment('Plumillas');
      $table->boolean('antenna')->default(true)->comment('Antena');
      $table->boolean('glasses_wheel')->default(true)->comment('Vasos Rueda');
      $table->boolean('emblems')->default(true)->comment('Emblemas');
      $table->boolean('spare_tire')->default(true)->comment('Llanta Repuesto');
      $table->boolean('fluid_caps')->default(true)->comment('Tapas Fluido');
      $table->boolean('tool_kit')->default(true)->comment('Kit Herramientas');
      $table->boolean('jack_and_lever')->default(true)->comment('Gata y Palanca');

      // General observations
      $table->text('general_observations')->nullable()
        ->comment('Observaciones generales de la inspección');

      // Inspector and signatures
      $table->integer('inspected_by')->comment('Quien hizo la inspección');
      $table->foreign('inspected_by')->references('id')->on('usr_users')->onDelete('cascade');

      // Inspection date
      $table->dateTime('inspection_date')->comment('Fecha y hora de la inspección');

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index(['work_order_id']);
      $table->index('inspection_date');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_vehicle_inspection');
  }
};
