<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('ap_mkt_plans', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('brand_id')->nullable();
      $table->string('name', 150);
      $table->string('concept', 150)->nullable();
      $table->smallInteger('year');
      $table->text('description')->nullable();
      $table->enum('status', ['draft', 'active', 'closed', 'cancelled'])->default('draft');
      $table->unsignedBigInteger('created_by')->nullable();
      $table->unsignedBigInteger('updated_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('brand_id')->references('id')->on('ap_vehicle_brand')->nullOnDelete();
      $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
      $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_mkt_plans');
  }
};
