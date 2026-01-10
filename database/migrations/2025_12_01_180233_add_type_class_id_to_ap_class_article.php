<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('ap_class_article', function (Blueprint $table) {
      // Agregar columna nullable (algunas clases como REPUESTOS no tienen clasificación)
      $table->foreignId('type_class_id')
        ->nullable()
        ->after('type_operation_id')
        ->constrained('ap_masters')
        ->nullOnDelete();

      // Crear índice para búsquedas rápidas
      $table->index('type_class_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_class_article', function (Blueprint $table) {
      $table->dropForeign(['type_class_id']);
      $table->dropIndex(['type_class_id']);
      $table->dropColumn('type_class_id');
    });
  }
};
