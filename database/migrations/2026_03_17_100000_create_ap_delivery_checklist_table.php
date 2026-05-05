<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('ap_delivery_checklist', function (Blueprint $table) {
      $table->id();
      $table->foreignId('vehicle_delivery_id')->constrained('ap_vehicle_delivery')->cascadeOnDelete();
      $table->text('observations')->nullable();
      $table->enum('status', ['draft', 'confirmed'])->default('draft');
      $table->timestamp('confirmed_at')->nullable();
      $table->integer('confirmed_by')->nullable();
      $table->integer('created_by')->nullable();
      $table->foreign('confirmed_by')->references('id')->on('usr_users')->nullOnDelete();
      $table->foreign('created_by')->references('id')->on('usr_users')->nullOnDelete();
      $table->timestamps();
      $table->softDeletes();

      $table->unique('vehicle_delivery_id');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_delivery_checklist');
  }
};
