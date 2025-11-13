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
    Schema::table('shipping_guides', function (Blueprint $table) {
      $table->unsignedBigInteger('transport_company_id')->nullable()->change();
      $table->string('origin_ubigeo')->nullable()->after('transport_company_id');
      $table->string('origin_address')->nullable()->after('origin_ubigeo');
      $table->string('destination_ubigeo')->nullable()->after('origin_address');
      $table->string('destination_address')->nullable()->after('destination_ubigeo');
      $table->string('ruc_transport')->nullable()->after('transport_company_id');
      $table->string('company_name_transport')->nullable()->after('ruc_transport');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('shipping_guides', function (Blueprint $table) {
      $table->unsignedBigInteger('transport_company_id')->nullable(false)->change();
      $table->dropColumn('origin_ubigeo');
      $table->dropColumn('origin_address');
      $table->dropColumn('destination_ubigeo');
      $table->dropColumn('destination_address');
      $table->dropColumn('ruc_transport');
      $table->dropColumn('company_name_transport');
    });
  }
};
