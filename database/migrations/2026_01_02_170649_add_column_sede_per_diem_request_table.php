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
    Schema::table('gh_per_diem_request', function (Blueprint $table) {
      $table->integer('sede_service_id')->nullable()->after('company_id');
      $table->foreign('sede_service_id')->references('id')->on('config_sede');
      //eliminamos company_service_id
      $table->dropForeign(['company_service_id']);
      $table->dropColumn('company_service_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_per_diem_request', function (Blueprint $table) {
      $table->dropForeign(['sede_service_id']);
      $table->dropColumn('sede_service_id');
      // recreamos company_service_id
      $table->integer('company_service_id')->nullable()->after('company_id');
      $table->foreign('company_service_id')->references('id')->on('companies');
    });
  }
};
