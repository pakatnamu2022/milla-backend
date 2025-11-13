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
    Schema::create('ap_post_venta_masters', function (Blueprint $table) {
      $table->id();
      $table->string('code', 50)->nullable();
      $table->string('description', 255);
      $table->string('type', 100);
      $table->boolean('status')->default(true);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_post_venta_masters');
  }
};
