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
    Schema::table('potential_buyers', function (Blueprint $table) {
      $table->enum('status_num_doc', ['PENDIENTE', 'VALIDADO', 'ERRADO', 'NO_ENCONTRADO'])->default('PENDIENTE')->after('num_doc');
      $table->boolean('use')->default(false)->after('status_num_doc');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('potential_buyers', function (Blueprint $table) {
      $table->dropColumn('status_num_doc');
      $table->dropColumn('use');
    });
  }
};
