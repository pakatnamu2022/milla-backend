<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('ap_mkt_plans', function (Blueprint $table) {
      $table->id();
      $table->string('name', 150);
      $table->string('concept', 150)->nullable();
      $table->smallInteger('year');
      $table->text('description')->nullable();
      $table->enum('status', ['draft', 'active', 'closed', 'cancelled'])->default('draft');
      $table->integer('created_by')->nullable();
      $table->integer('updated_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreignId('brand_id')->constrained('ap_vehicle_brand');
      $table->foreign('created_by')->references('id')->on('usr_users')->nullOnDelete();
      $table->foreign('updated_by')->references('id')->on('usr_users')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_mkt_plans');
  }
};
