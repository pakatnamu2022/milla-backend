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
    Schema::create('ap_vehicle_inspection_damages', function (Blueprint $table) {
      $table->id();

      // Relations
      $table->foreignId('vehicle_inspection_id')->comment('Inspección asociada')
        ->constrained('ap_vehicle_inspection')->onDelete('cascade');

      // Damage details
      $table->string('damage_type')->comment('Tipo de daño:DAÑOS EN PINTURA, ABOLLADURAS, RAYADURAS, DAÑOS EN CARROCERÍA, ETC.');
      $table->string('x_coordinate')->comment('Coordenada X del daño en el vehículo');
      $table->string('y_coordinate')->comment('Coordenada Y del daño en el vehículo');
      $table->text('description')->nullable()->comment('Descripción detallada del daño');

      // Photo evidence
      $table->string('photo_url')->nullable()->comment('URL de la foto del daño');

      $table->timestamps();
      $table->softDeletes();

      // Index
      $table->index('vehicle_inspection_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_vehicle_inspection_damages');
  }
};
