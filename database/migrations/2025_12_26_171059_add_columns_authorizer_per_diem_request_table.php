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
      $table->integer('authorizer_id')->nullable()->after('per_diem_category_id');
      $table->foreign('authorizer_id')->references('id')->on('rrhh_persona')->onDelete('restrict');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_per_diem_request', function (Blueprint $table) {
      $table->dropForeign(['authorizer_id']);
      $table->dropColumn('authorizer_id');
    });
  }
};
