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
    Schema::table('ap_bank', function (Blueprint $table) {
      $table->integer('sede_id')->nullable()->after('id'); // Cambiado a unsignedInteger
      $table->foreign('sede_id', 'ap_bank_sede_id_foreign')
        ->references('id')->on('config_sede')
        ->onUpdate('cascade')
        ->onDelete('restrict');

      $table->unsignedBigInteger('company_branch_id')->nullable()->change();
    });


    Schema::table('ap_assign_brand_consultant', function (Blueprint $table) {
      $table->integer('sede_id')->nullable()->after('id'); // Cambiado a unsignedInteger
      $table->foreign('sede_id', 'ap_assign_brand_consultant_sede_id_foreign')
        ->references('id')->on('config_sede')
        ->onUpdate('cascade')
        ->onDelete('restrict');

      $table->unsignedBigInteger('company_branch_id')->nullable()->change();
    });

    Schema::table('ap_assign_company_branch', function (Blueprint $table) {
      $table->integer('sede_id')->nullable()->after('id'); // Cambiado a unsignedInteger
      $table->foreign('sede_id', 'ap_assign_company_branch_sede_id_foreign')
        ->references('id')->on('config_sede')
        ->onUpdate('cascade')
        ->onDelete('restrict');

      $table->unsignedBigInteger('company_branch_id')->nullable()->change();
    });

    Schema::table('ap_assign_company_branch_period', function (Blueprint $table) {
      $table->integer('sede_id')->nullable()->after('id'); // Cambiado a unsignedInteger
      $table->foreign('sede_id', 'ap_assign_company_branch_period_sede_id_foreign')
        ->references('id')->on('config_sede')
        ->onUpdate('cascade')
        ->onDelete('restrict');

      $table->unsignedBigInteger('company_branch_id')->nullable()->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    // Revertir cambios
    Schema::table('ap_bank', function (Blueprint $table) {
      $table->dropForeign('ap_bank_sede_id_foreign');
      $table->dropColumn('sede_id');
    });

    Schema::table('ap_assign_brand_consultant', function (Blueprint $table) {
      $table->dropForeign('ap_assign_brand_consultant_sede_id_foreign');
      $table->dropColumn('sede_id');
    });

    Schema::table('ap_assign_company_branch', function (Blueprint $table) {
      $table->dropForeign('ap_assign_company_branch_sede_id_foreign');
      $table->dropColumn('sede_id');
    });

    Schema::table('ap_assign_company_branch_period', function (Blueprint $table) {
      $table->dropForeign('ap_assign_company_branch_period_sede_id_foreign');
      $table->dropColumn('sede_id');
    });
  }
};
