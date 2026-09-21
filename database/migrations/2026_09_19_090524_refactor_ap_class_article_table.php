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
    Schema::table('ap_class_article', function (Blueprint $table) {
      $table->renameColumn('account', 'inventory_account');
      $table->string('counterparty_account', 20)->nullable()->after('inventory_account');
      $table->string('account_sales', 20)->nullable()->after('counterparty_account');
    });

    Schema::table('ap_class_article', function (Blueprint $table) {
      $table->string('inventory_account', 20)->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_class_article', function (Blueprint $table) {
      $table->string('inventory_account', 150)->change(); // usa el tamaño original
    });

    Schema::table('ap_class_article', function (Blueprint $table) {
      $table->renameColumn('inventory_account', 'account');
      $table->dropColumn('counterparty_account');
      $table->dropColumn('account_sales');
    });
  }
};
