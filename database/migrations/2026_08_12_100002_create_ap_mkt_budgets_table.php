<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('ap_mkt_budgets', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('plan_id');
      $table->enum('type', ['regular', 'additional'])->default('regular');
      $table->tinyInteger('period_month')->nullable()->comment('1-12, null = anual');
      $table->unsignedBigInteger('currency_id');
      $table->decimal('amount_estimated', 14, 2)->default(0);
      $table->decimal('amount_executed', 14, 2)->default(0);
      $table->enum('status', ['draft', 'approved', 'closed'])->default('draft');
      $table->text('notes')->nullable();
      $table->unsignedBigInteger('created_by')->nullable();
      $table->unsignedBigInteger('updated_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('plan_id')->references('id')->on('ap_mkt_plans')->cascadeOnDelete();
      $table->foreign('currency_id')->references('id')->on('type_currency');
      $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
      $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_mkt_budgets');
  }
};
