<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('ap_mkt_kpis', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('activity_id');
      $table->tinyInteger('period_month')->nullable();
      $table->smallInteger('period_year')->nullable();
      $table->integer('leads')->default(0);
      $table->integer('sales')->default(0);
      $table->decimal('investment', 14, 2)->default(0);
      $table->unsignedBigInteger('currency_id')->nullable();
      $table->text('notes')->nullable();
      $table->unsignedBigInteger('created_by')->nullable();
      $table->unsignedBigInteger('updated_by')->nullable();
      $table->timestamps();

      $table->foreign('activity_id')->references('id')->on('ap_mkt_activities')->cascadeOnDelete();
      $table->foreign('currency_id')->references('id')->on('type_currency')->nullOnDelete();
      $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
      $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_mkt_kpis');
  }
};
