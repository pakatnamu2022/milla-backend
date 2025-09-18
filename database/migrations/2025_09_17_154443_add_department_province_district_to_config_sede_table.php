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
    Schema::table('config_sede', function (Blueprint $table) {
      // Agregamos columna de dyn_code y establishment
      $table->string('dyn_code')->nullable()->after('abreviatura');
      $table->string('establishment')->nullable()->after('dyn_code');
      $table->boolean('status')->default(true)->after('establishment');

      // Agregar las columnas
      $table->unsignedBigInteger('department_id')->nullable();
      $table->unsignedBigInteger('province_id')->nullable();
      $table->unsignedBigInteger('district_id')->nullable();

      //agregamos el SoftDeletes
      $table->softDeletes();

      // Agregar las claves foráneas
      $table->foreign('department_id')->references('id')->on('department')->onDelete('set null');
      $table->foreign('province_id')->references('id')->on('province')->onDelete('set null');
      $table->foreign('district_id')->references('id')->on('district')->onDelete('set null');

      // Agregar índices para mejor performance
      $table->index('department_id');
      $table->index('province_id');
      $table->index('district_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('config_sede', function (Blueprint $table) {
      $table->dropColumn('dyn_code');
      $table->dropColumn('establishment');
      $table->dropColumn('status');

      // Eliminar claves foráneas primero
      $table->dropForeign(['department_id']);
      $table->dropForeign(['province_id']);
      $table->dropForeign(['district_id']);

      // Eliminamos SoftDeletes
      $table->dropSoftDeletes();

      // Eliminar columnas
      $table->dropColumn(['department_id', 'province_id', 'district_id']);
    });
  }
};
