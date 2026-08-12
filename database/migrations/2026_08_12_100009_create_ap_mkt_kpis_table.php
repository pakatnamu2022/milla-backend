<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('ap_mkt_kpis', function (Blueprint $table) {
      $table->id();
      $table->tinyInteger('period_month')->nullable();
      $table->smallInteger('period_year')->nullable();
      $table->integer('leads')->default(0);
      $table->integer('sales')->default(0);
      $table->decimal('investment', 14, 2)->default(0);
      $table->text('notes')->nullable();
      $table->integer('created_by')->nullable();
      $table->integer('updated_by')->nullable();
      $table->timestamps();


      $table->foreignId('activity_id')->constrained('ap_mkt_activities');
      $table->foreignId('currency_id')->constrained('type_currency');
      $table->foreign('created_by')->references('id')->on('usr_users')->nullOnDelete();
      $table->foreign('updated_by')->references('id')->on('usr_users')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_mkt_kpis');
  }
};
