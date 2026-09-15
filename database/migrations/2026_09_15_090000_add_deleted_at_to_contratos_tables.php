<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega `deleted_at` (SoftDeletes nativo) a las tablas del módulo Contratos.
 * Conviven con `status_deleted` (columna legacy que sigue usando web_millagp_2):
 * los modelos sincronizan ambas columnas al eliminar/restaurar, ver
 * App\Http\Traits\SyncsLegacySoftDeletes.
 */
return new class extends Migration
{
  private array $tables = [
    'rrhh_contrato',
    'rrhh_firmante',
    'rrhh_plantilla_contrato',
    'rrhh_tipo_contrato',
  ];

  public function up(): void
  {
    foreach ($this->tables as $table) {
      if (!Schema::hasColumn($table, 'deleted_at')) {
        Schema::table($table, function (Blueprint $blueprint) {
          $blueprint->softDeletes();
        });
      }
    }
  }

  public function down(): void
  {
    foreach ($this->tables as $table) {
      if (Schema::hasColumn($table, 'deleted_at')) {
        Schema::table($table, function (Blueprint $blueprint) {
          $blueprint->dropSoftDeletes();
        });
      }
    }
  }
};
