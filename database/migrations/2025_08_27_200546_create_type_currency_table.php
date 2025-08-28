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
    Schema::create('type_currency', function (Blueprint $table) {
      $table->id();
      $table->string('codigo', 3)->unique(); // (ISO 4217, ej. "PEN", "USD", "EUR")
      $table->string('nombre', 50); // Soles
      $table->string('simbolo', 5)->nullable(); // (ej. "S/", "$", "€")
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
    Schema::dropIfExists('type_currency');
  }
};
