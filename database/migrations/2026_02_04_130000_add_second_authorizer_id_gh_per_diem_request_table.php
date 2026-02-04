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
      $table->integer('second_authorizer_id')->nullable()->after('authorizer_id');
      $table->foreign('second_authorizer_id')->references('id')->on('rrhh_persona')->onDelete('restrict');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_per_diem_request', function (Blueprint $table) {
      $table->dropForeign(['second_authorizer_id']);
      $table->dropColumn('second_authorizer_id');
    });
  }
};
