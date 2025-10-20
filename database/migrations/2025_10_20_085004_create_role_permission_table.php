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
    Schema::create('role_permission', function (Blueprint $table) {
      $table->id();
      $table->integer('role_id');
      $table->foreign('role_id')->references('id')->on('config_roles')->onDelete('cascade');

      $table->foreignId('permission_id')
        ->constrained('permission')
        ->onDelete('cascade')
        ->onUpdate('cascade')
        ->comment('ID del permiso');

      $table->boolean('granted')->default(true)->comment('Si el permiso está otorgado (true) o denegado (false)');
      $table->timestamps();

      // Índice único para evitar duplicados
      $table->unique(['role_id', 'permission_id']);

      // Índices para optimizar consultas
      $table->index('granted');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('role_permission');
  }
};
