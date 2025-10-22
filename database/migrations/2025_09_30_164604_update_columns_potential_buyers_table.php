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
      // quitamos los campos de name y surnames y ponemos full_name
      $table->dropColumn('name');
      $table->dropColumn('surnames');
      $table->string('full_name')->after('num_doc');
      $table->integer('worker_id')->after('type')->nullable();
      $table->foreign('worker_id')
        ->references('id')
        ->on('usr_users');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('potential_buyers', function (Blueprint $table) {
      $table->string('name')->after('num_doc');
      $table->string('surnames')->after('name')->nullable();
      $table->dropColumn('full_name');
      $table->dropForeign(['worker_id']);
      $table->dropColumn('worker_id');
    });
  }
};
