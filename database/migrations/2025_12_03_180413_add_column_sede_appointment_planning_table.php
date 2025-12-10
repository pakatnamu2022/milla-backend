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
    Schema::table('appointment_planning', function (Blueprint $table) {
      $table->integer('sede_id')->nullable()->comment('Sede donde se realiza el servicio');
      $table->foreign('sede_id')->references('id')->on('config_sede')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('appointment_planning', function (Blueprint $table) {
      $table->dropForeign(['sede_id']);
      $table->dropColumn('sede_id');
    });
  }
};
