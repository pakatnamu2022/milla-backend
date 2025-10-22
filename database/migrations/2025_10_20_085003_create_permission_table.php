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
    Schema::create('permission', function (Blueprint $table) {
      $table->id();
      $table->string('code')->unique()->comment('Código único del permiso: module.action');
      $table->string('name')->comment('Nombre descriptivo del permiso');
      $table->text('description')->nullable()->comment('Descripción detallada del permiso');
      $table->string('module')->index()->comment('Módulo al que pertenece: vehicle_purchase_order, opportunity, etc.');
      $table->string('policy_method')->nullable()->comment('Nombre del método en la Policy correspondiente');
      $table->enum('type', ['basic', 'special', 'custom'])->default('custom')->comment('Tipo de permiso: basic (CRUD), special (común), custom (específico)');
      $table->boolean('is_active')->default(true)->comment('Estado del permiso');
      $table->timestamps();
      $table->softDeletes();

      // Índices para optimizar consultas
      $table->index(['module', 'is_active']);
      $table->index('type');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('permission');
  }
};
