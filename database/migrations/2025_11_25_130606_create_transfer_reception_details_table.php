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
    Schema::create('transfer_reception_details', function (Blueprint $table) {
      $table->id();
      $table->foreignId('transfer_reception_id')->constrained('transfer_receptions')->onDelete('cascade');
      $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
      $table->decimal('quantity_sent', 10, 2);
      $table->decimal('quantity_received', 10, 2);
      $table->decimal('observed_quantity', 10, 2)->default(0);
      $table->string('reason_observation')->nullable();
      $table->text('observation_notes')->nullable();
      $table->decimal('unit_cost', 10, 2)->nullable();
      $table->decimal('total_cost', 12, 2)->nullable();
      $table->string('batch_number')->nullable();
      $table->date('expiration_date')->nullable();
      $table->timestamps();

      $table->index('transfer_reception_id');
      $table->index('product_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('transfer_reception_details');
  }
};
