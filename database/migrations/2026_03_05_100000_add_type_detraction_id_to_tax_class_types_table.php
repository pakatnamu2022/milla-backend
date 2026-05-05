<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('tax_class_types', function (Blueprint $table) {
      $table->foreignId('type_detraction_id')
        ->nullable()
        ->after('igv')
        ->constrained('sunat_concepts')
        ->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('tax_class_types', function (Blueprint $table) {
      $table->dropForeign(['type_detraction_id']);
      $table->dropColumn('type_detraction_id');
    });
  }
};
