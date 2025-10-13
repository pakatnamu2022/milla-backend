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
      $table->enum('type', ['ACCESORIO_ADICIONAL', 'OBSEQUIO']);
      $table->integer('quantity');
      $table->decimal('price', 12, 4);
      $table->decimal('total', 12, 4);
      $table->foreignId('purchase_request_quote_id')
        ->constrained('purchase_request_quote', indexName: 'det_acc_prq_fk')
        ->onDelete('cascade');
      $table->foreignId('approved_accessory_id')
        ->constrained('approved_accessories')->onDelete('cascade');
      $table->timestamps();
      $table->softDeletes();
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
