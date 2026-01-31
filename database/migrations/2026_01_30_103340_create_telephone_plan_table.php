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
    Schema::create('telephone_plan', function (Blueprint $table) {
      $table->id();
      $table->string('name')->comment('Nombre del plan, ej: Max Negocios + 29.90');
      $table->decimal('price', 10)->comment('Precio del plan');
      $table->text('description')->nullable()->comment('Descripción adicional del plan');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('telephone_plan');
  }
};
