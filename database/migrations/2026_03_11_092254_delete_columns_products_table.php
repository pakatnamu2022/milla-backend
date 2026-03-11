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
    Schema::table('products', function (Blueprint $table) {
      $table->dropColumn('nubefac_code');
      $table->dropColumn('cost_price');
      $table->dropColumn('sale_price');
      $table->dropColumn('tax_rate');
      $table->dropColumn('is_taxable');
      $table->dropColumn('sunat_code');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('products', function (Blueprint $table) {
      $table->string('nubefac_code', 50)->nullable()->after('dyn_code');
      $table->decimal('cost_price', 15)->after('ap_class_article_id');
      $table->decimal('sale_price', 15)->after('cost_price');
      $table->decimal('tax_rate', 5)->after('sale_price');
      $table->boolean('is_taxable')->default(true)->after('tax_rate');
      $table->string('sunat_code', 20)->nullable()->after('is_taxable');
    });
  }
};
