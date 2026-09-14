<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('accounts_payable', function (Blueprint $table) {
      $table->string('company', 50)->default('automotores')->after('id');
    });

    // Backfill: registros existentes fueron sincronizados desde dbtp3 (automotores).
    DB::table('accounts_payable')->update(['company' => 'automotores']);

    Schema::table('accounts_payable', function (Blueprint $table) {
      $table->dropUnique(['documento']);
      $table->unique(['company', 'documento']);
      $table->index('company');
    });
  }

  public function down(): void
  {
    Schema::table('accounts_payable', function (Blueprint $table) {
      $table->dropUnique(['company', 'documento']);
      $table->dropIndex(['company']);
      $table->unique('documento');
      $table->dropColumn('company');
    });
  }
};
