<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->string('credit_type')->nullable()->after('is_approved');
      $table->string('credit_entity')->nullable()->after('credit_type');
      $table->string('insurance_entity')->nullable()->after('credit_entity');
      $table->unsignedTinyInteger('gps_hunter_years')->nullable()->after('insurance_entity');
    });
  }

  public function down(): void
  {
    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->dropColumn(['credit_type', 'credit_entity', 'insurance_entity', 'gps_hunter_years']);
    });
  }
};
