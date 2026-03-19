<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('ap_delivery_checklist_item', function (Blueprint $table) {
      $table->id();
      $table->foreignId('delivery_checklist_id')->constrained('ap_delivery_checklist')->cascadeOnDelete();
      $table->enum('source', ['reception', 'purchase_order', 'manual'])->default('manual');
      $table->unsignedBigInteger('source_id')->nullable();
      $table->string('description', 255);
      $table->decimal('quantity', 10, 2)->default(1);
      $table->string('unit', 50)->nullable();
      $table->boolean('is_confirmed')->default(false);
      $table->string('observations', 500)->nullable();
      $table->unsignedSmallInteger('sort_order')->default(0);
      $table->timestamps();
      $table->softDeletes();

      $table->index(['delivery_checklist_id', 'source']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_delivery_checklist_item');
  }
};
