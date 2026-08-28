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
    Schema::create('product_shelves', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('warehouse_id');
      $table->string('code', 50)->unique();
      $table->string('label');
      $table->text('notes')->nullable();
      $table->boolean('status')->default(true);
      $table->integer('created_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('warehouse_id')->references('id')->on('warehouse')->onDelete('cascade');
      $table->foreign('created_by')->references('id')->on('usr_users')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('product_shelves');
  }
};
