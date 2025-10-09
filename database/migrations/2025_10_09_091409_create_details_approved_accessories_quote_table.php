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
    Schema::create('details_approved_accessories_quote', function (Blueprint $table) {
      $table->id();
      $table->integer('quantity');
      $table->decimal('price', 12, 4);
      $table->decimal('total', 12, 4);
      $table->decimal('exchange_rate', 12, 4)->nullable();
      $table->foreignId('purchase_request_quote_id')
        ->constrained('purchase_request_quote', indexName: 'det_acc_prq_fk')
        ->onDelete('cascade');
      $table->foreignId('approved_accessory_id')
        ->constrained('approved_accessories')->onDelete('cascade');
      $table->foreignId('type_currency_id')
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('details_approved_accessories_quote');
  }
};
