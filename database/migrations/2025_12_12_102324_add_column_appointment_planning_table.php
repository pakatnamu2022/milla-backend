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
    Schema::table('appointment_planning', function (Blueprint $table) {
      $table->string('num_doc_client')->nullable()->after('time_appointment');
      $table->foreignId('owner_id')->nullable()->after('advisor_id')->constrained('business_partners');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('appointment_planning', function (Blueprint $table) {
      $table->dropColumn('num_doc_client');
      $table->dropForeign(['owner_id']);
      $table->dropColumn('owner_id');
    });
  }
};
