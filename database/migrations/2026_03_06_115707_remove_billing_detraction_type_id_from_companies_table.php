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
    Schema::table('companies', function (Blueprint $table) {
      $table->dropForeign(['billing_detraction_type_id']);
      $table->dropColumn('billing_detraction_type_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('companies', function (Blueprint $table) {
      $table->foreignId('billing_detraction_type_id')
            ->nullable()
            ->constrained('sunat_concepts');
    });
  }
};
