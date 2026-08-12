<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('mkt_budget_fundings', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('budget_id');
      $table->enum('source', ['AP', 'brand']);
      $table->unsignedBigInteger('currency_id');
      $table->decimal('amount', 14, 2)->default(0);
      $table->text('notes')->nullable();
      $table->timestamps();

      $table->foreign('budget_id')->references('id')->on('mkt_budgets')->cascadeOnDelete();
      $table->foreign('currency_id')->references('id')->on('type_currency');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('mkt_budget_fundings');
  }
};
