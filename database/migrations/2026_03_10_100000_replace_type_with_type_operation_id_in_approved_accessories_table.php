<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  // TIPO_OPERACION_POSTVENTA = 804 (default para registros existentes)
  const DEFAULT_TYPE_OPERATION_ID = 804;

  public function up(): void
  {
    Schema::table('approved_accessories', function (Blueprint $table) {
      $table->dropColumn('type');
    });
    Schema::table('approved_accessories', function (Blueprint $table) {
      $table->unsignedBigInteger('type_operation_id')
        ->nullable()
        ->after('code');
    });

    // Poblar filas existentes con un valor válido antes de agregar el FK
    DB::table('approved_accessories')
      ->whereNull('type_operation_id')
      ->update(['type_operation_id' => self::DEFAULT_TYPE_OPERATION_ID]);

    Schema::table('approved_accessories', function (Blueprint $table) {
      $table->unsignedBigInteger('type_operation_id')->nullable(false)->change();
      $table->foreign('type_operation_id')
        ->references('id')->on('ap_masters')->onDelete('cascade');
    });
  }

  public function down(): void
  {
    Schema::table('approved_accessories', function (Blueprint $table) {
      $table->dropForeign(['type_operation_id']);
      $table->dropColumn('type_operation_id');
    });
    Schema::table('approved_accessories', function (Blueprint $table) {
      $table->enum('type', ['SERVICIO', 'REPUESTO'])->after('code');
    });
  }
};
