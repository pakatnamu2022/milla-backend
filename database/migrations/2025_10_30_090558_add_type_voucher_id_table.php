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
      $table->foreignId('type_voucher_id')->nullable()->constrained('sunat_concepts')->after('document_type');
      $table->boolean('status_dynamic')->after('type_voucher_id')->default(false);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('shipping_guides', function (Blueprint $table) {
      //
      $table->dropForeign(['type_voucher_id']);
      $table->dropColumn('type_voucher_id');
      $table->dropColumn('status_dynamic');
    });
  }
};
