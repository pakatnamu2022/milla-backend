<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('ap_mkt_budgets', function (Blueprint $table) {
      $table->id();
      $table->enum('type', ['regular', 'additional'])->default('regular');
      $table->tinyInteger('period_month')->nullable()->comment('1-12, null = anual');
      $table->decimal('amount_estimated', 14, 2)->default(0);
      $table->decimal('amount_executed', 14, 2)->default(0);
      $table->enum('status', ['draft', 'approved', 'closed'])->default('draft');
      $table->text('notes')->nullable();
      $table->integer('created_by')->nullable();
      $table->integer('updated_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreignId('plan_id')->constrained('ap_mkt_plans');
      $table->foreignId('currency_id')->constrained('type_currency');
      $table->foreign('created_by')->references('id')->on('usr_users')->nullOnDelete();
      $table->foreign('updated_by')->references('id')->on('usr_users')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_mkt_budgets');
  }
};
