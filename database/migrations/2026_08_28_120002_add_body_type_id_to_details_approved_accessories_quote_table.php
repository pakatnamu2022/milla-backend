<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('details_approved_accessories_quote', function (Blueprint $table) {
      $table->unsignedBigInteger('body_type_id')->nullable()->after('approved_accessory_id');
      $table->foreign('body_type_id')->references('id')->on('ap_masters')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('details_approved_accessories_quote', function (Blueprint $table) {
      $table->dropForeign(['body_type_id']);
      $table->dropColumn('body_type_id');
    });
  }
};
