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
    Schema::table('business_partners', function (Blueprint $table) {
      $table->foreignId('tax_class_type_id')->nullable()->change();
      $table->foreignId('supplier_tax_class_id')->nullable()
        ->constrained('tax_class_types')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('business_partners', function (Blueprint $table) {
      $table->foreignId('tax_class_type_id')->nullable(false)->change();
      $table->dropForeign(['supplier_tax_class_id']);
      $table->dropColumn('supplier_tax_class_id');
    });
  }
};
