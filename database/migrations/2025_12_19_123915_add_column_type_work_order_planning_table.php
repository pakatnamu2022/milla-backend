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
    Schema::table('work_order_planning', function (Blueprint $table) {
      $table->enum('type', ['internal', 'external'])->default('internal')->after('status')
        ->comment('Tipo de planificación: interna (dentro del rango de lina de tiempo), externa (fuera del rango de línea de tiempo). Avanzo mas rapido de lo esperado');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('work_order_planning', function (Blueprint $table) {
      //
    });
  }
};
