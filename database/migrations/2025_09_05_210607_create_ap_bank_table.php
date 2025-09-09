<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('ap_bank', function (Blueprint $table) {
      $table->id();
      $table->string('code', 50)->unique();
      $table->string('account_number', 50)->unique()->nullable();
      $table->string('cci', 50)->unique()->nullable();
      $table->foreignId('bank_id')
        ->constrained('ap_commercial_masters');
      $table->foreignId('currency_id')
        ->constrained('type_currency');
      $table->foreignId('company_branch_id')
        ->constrained('company_branch');
      $table->boolean('status')->default(true);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_bank');
  }
};
