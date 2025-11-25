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
    Schema::create('transfer_receptions', function (Blueprint $table) {
      $table->id();
      $table->string('reception_number')->unique();
      $table->foreignId('transfer_movement_id')->constrained('inventory_movements')->onDelete('cascade');
      $table->foreignId('shipping_guide_id')->constrained('shipping_guides')->onDelete('cascade');
      $table->foreignId('warehouse_id')->constrained('warehouse');
      $table->date('reception_date');
      $table->string('status')->default('PENDING');
      $table->text('notes')->nullable();
      $table->integer('received_by');
      $table->foreign('received_by')->references('id')->on('usr_users')->onDelete('cascade');
      $table->integer('reviewed_by')->nullable();
      $table->foreign('reviewed_by')->references('id')->on('usr_users')->onDelete('set null');
      $table->timestamp('reviewed_at')->nullable();
      $table->integer('total_items')->default(0);
      $table->decimal('total_quantity', 10, 2)->default(0);
      $table->timestamps();
      $table->softDeletes();

      $table->index('transfer_movement_id');
      $table->index('shipping_guide_id');
      $table->index('warehouse_id');
      $table->index('status');
      $table->index('reception_date');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('transfer_receptions');
  }
};
