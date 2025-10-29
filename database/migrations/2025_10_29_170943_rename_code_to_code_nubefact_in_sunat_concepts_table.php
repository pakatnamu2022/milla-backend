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
    Schema::table('sunat_concepts', function (Blueprint $table) {
      $table->renameColumn('code', 'code_nubefact');
      $table->dropUnique(['code']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('sunat_concepts', function (Blueprint $table) {
      $table->renameColumn('code_nubefact', 'code');
      $table->unique('code');
    });
  }
};
