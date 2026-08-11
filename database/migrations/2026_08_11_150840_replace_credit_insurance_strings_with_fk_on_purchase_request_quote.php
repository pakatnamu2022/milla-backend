<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->dropColumn(['credit_type', 'credit_entity', 'insurance_entity']);
    });

    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->foreignId('credit_type_id')->nullable()->after('is_approved')->constrained('ap_masters')->nullOnDelete();
      $table->foreignId('credit_entity_id')->nullable()->after('credit_type_id')->constrained('ap_masters')->nullOnDelete();
      $table->foreignId('insurance_entity_id')->nullable()->after('credit_entity_id')->constrained('ap_masters')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->dropForeign(['credit_type_id']);
      $table->dropForeign(['credit_entity_id']);
      $table->dropForeign(['insurance_entity_id']);
      $table->dropColumn(['credit_type_id', 'credit_entity_id', 'insurance_entity_id']);
    });

    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->string('credit_type')->nullable()->after('is_approved');
      $table->string('credit_entity')->nullable()->after('credit_type');
      $table->string('insurance_entity')->nullable()->after('credit_entity');
    });
  }
};
