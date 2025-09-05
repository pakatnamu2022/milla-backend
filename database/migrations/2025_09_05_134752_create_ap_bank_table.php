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
    Schema::create('ap_bank', function (Blueprint $table) {
      $table->id();
      $table->string('codigo', 50)->unique();
      $table->string('numero_cuenta', 50)->unique()->nullable();
      $table->string('cci', 50)->unique()->nullable();
      $table->foreignId('banco_id')
        ->constrained('ap_commercial_masters');
      $table->foreignId('moneda_id')
        ->constrained('type_currency');
      $table->integer('sede_id');
      $table->foreign('sede_id')->references('id')->on('config_sede');
      $table->boolean('status')->default(true);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_bank');
  }
};
