<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('mkt_activity_locations', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('activity_id');
      $table->unsignedBigInteger('sede_id')->nullable();
      $table->string('location_name', 150)->nullable();
      $table->unsignedBigInteger('currency_id')->nullable();
      $table->decimal('amount', 14, 2)->default(0);
      $table->text('notes')->nullable();
      $table->timestamps();

      $table->foreign('activity_id')->references('id')->on('mkt_activities')->cascadeOnDelete();
      $table->foreign('sede_id')->references('id')->on('config_sede')->nullOnDelete();
      $table->foreign('currency_id')->references('id')->on('type_currency')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('mkt_activity_locations');
  }
};
