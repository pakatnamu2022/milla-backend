<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('ap_mkt_budget_fundings', function (Blueprint $table) {
      $table->id();
      $table->enum('source', ['AP', 'brand']);
      $table->decimal('amount', 14, 2)->default(0);
      $table->text('notes')->nullable();
      $table->timestamps();

      $table->foreignId('budget_id')->constrained('ap_mkt_budgets');
      $table->foreignId('currency_id')->constrained('type_currency');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_mkt_budget_fundings');
  }
};
