<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->decimal('a_cuenta', 12, 4)->nullable()->default(null)
        ->comment('Monto de anticipo o a cuenta ingresado por el cliente')
        ->after('doc_sale_price');
    });
  }

  public function down(): void
  {
    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->dropColumn('a_cuenta');
    });
  }
};
