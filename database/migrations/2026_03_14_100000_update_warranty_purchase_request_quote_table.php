<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->dropColumn('warranty');
      $table->unsignedInteger('warranty_years')->nullable()->after('comment');
      $table->unsignedInteger('warranty_km')->nullable()->after('warranty_years');
    });
  }

  public function down(): void
  {
    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->dropColumn(['warranty_years', 'warranty_km']);
      $table->string('warranty', 100)->nullable()->after('comment');
    });
  }
};
