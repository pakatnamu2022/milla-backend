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
    Schema::table('rrhh_cargo', function (Blueprint $table) {
      $table->boolean('is_risk_position')->default(false)->after('per_diem_category_id')
        ->comment('Puesto de riesgo para SCTR (mecanicos, auxiliares operativos, conductores, operarios)');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('rrhh_cargo', function (Blueprint $table) {
      $table->dropColumn('is_risk_position');
    });
  }
};
