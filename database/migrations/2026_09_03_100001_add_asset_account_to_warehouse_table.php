<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('warehouse', function (Blueprint $table) {
      $table->string('asset_account', 50)
        ->nullable()
        ->after('counterparty_account')
        ->comment('Cuenta contable de activos (33). Contrapartida al convertir un vehículo VN en activo.');
    });
  }

  public function down(): void
  {
    Schema::table('warehouse', function (Blueprint $table) {
      $table->dropColumn('asset_account');
    });
  }
};
