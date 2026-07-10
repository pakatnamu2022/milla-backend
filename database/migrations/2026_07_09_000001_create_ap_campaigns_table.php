<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('ap_campaigns', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('area_id')->nullable();
      $table->string('code', 50)->unique();
      $table->string('name', 150);
      $table->text('description')->nullable();
      $table->date('start_date');
      $table->date('end_date');
      $table->enum('discount_type', ['fixed', 'percentage']);
      $table->decimal('discount_value', 12, 2);
      $table->boolean('status')->default(true);
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('area_id')->references('id')->on('ap_masters')->onDelete('set null');
      $table->index(['start_date', 'end_date']);
      $table->index('status');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_campaigns');
  }
};
