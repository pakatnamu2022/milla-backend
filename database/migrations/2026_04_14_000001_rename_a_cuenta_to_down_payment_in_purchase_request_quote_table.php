<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->renameColumn('a_cuenta', 'down_payment');
    });
  }

  public function down(): void
  {
    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->renameColumn('down_payment', 'a_cuenta');
    });
  }
};
