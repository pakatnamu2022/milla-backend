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
    Schema::table('gh_config_competencias', function (Blueprint $table) {
      $table->dropForeign('gh_config_competencias_ibfk_1');
      $table->dropColumn('grupo_cargos_id');
      $table->dropColumn('status_delete');
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_config_competencias', function (Blueprint $table) {
      $table->unsignedBigInteger('grupo_cargos_id')->nullable();
      $table->integer('status_delete')->default(0);
      $table->dropSoftDeletes();
    });
  }
};
