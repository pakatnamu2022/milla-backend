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
    Schema::table('gh_evaluation_person', function (Blueprint $table) {
      $table->boolean('wasEvaluated')->default(false)->after('comment')->comment('Indicates if the person has been evaluated');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_evaluation_person', function (Blueprint $table) {
      $table->dropColumn('wasEvaluated');
    });
  }
};
