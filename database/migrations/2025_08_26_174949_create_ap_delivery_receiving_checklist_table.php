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
    Schema::create('ap_delivery_receiving_checklist', function (Blueprint $table) {
      $table->id();
      $table->string('description', 255);
      $table->enum('type', ['ENTREGA', 'RECEPCION']);
      $table->boolean('status')->default(true);
      $table->foreignId('category_id')
        ->constrained('ap_masters')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_delivery_receiving_checklist');
  }
};
