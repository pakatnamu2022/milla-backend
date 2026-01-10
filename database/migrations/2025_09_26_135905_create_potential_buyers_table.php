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
    Schema::create('potential_buyers', function (Blueprint $table) {
      $table->id();
      $table->date('registration_date');
      $table->string('model')->nullable();
      $table->string('version')->nullable();
      $table->string('num_doc');
      $table->string('name');
      $table->string('surnames')->nullable();
      $table->string('phone')->nullable();
      $table->string('email')->nullable();
      $table->string('campaign', 100)->comment("¿De qué campaña viene el cliente, derco, redes sociales?");
      $table->string('type')->comment("¿Es VISITA o LEADS, ETC?");
      $table->foreignId('income_sector_id')
        ->constrained('ap_masters')->onDelete('cascade');
      $table->integer('sede_id');
      $table->foreign('sede_id')->references('id')->on('config_sede');
      $table->foreignId('vehicle_brand_id')
        ->constrained('ap_vehicle_brand')->onDelete('cascade');
      $table->foreignId('document_type_id')
        ->constrained('ap_masters')->onDelete('cascade');
      $table->foreignId('area_id')
        ->constrained('ap_masters')->onDelete('cascade');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('potential_buyers');
  }
};
