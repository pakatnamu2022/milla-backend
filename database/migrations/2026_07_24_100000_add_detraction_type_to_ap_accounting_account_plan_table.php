<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('ap_accounting_account_plan', function (Blueprint $table) {
      if (Schema::hasColumn('ap_accounting_account_plan', 'sunat_concept_detraction_type_id')) {
        $table->dropColumn('sunat_concept_detraction_type_id');
      }
    });

    Schema::table('ap_accounting_account_plan', function (Blueprint $table) {
      $table->unsignedBigInteger('sunat_concept_detraction_type_id')->nullable()->after('detraction_percentage');
      $table->foreign('sunat_concept_detraction_type_id', 'ap_aaplan_detraction_type_fk')
        ->references('id')
        ->on('sunat_concepts');
    });
  }

  public function down(): void
  {
    Schema::table('ap_accounting_account_plan', function (Blueprint $table) {
      $table->dropForeign('ap_aaplan_detraction_type_fk');
      $table->dropColumn('sunat_concept_detraction_type_id');
    });
  }
};
