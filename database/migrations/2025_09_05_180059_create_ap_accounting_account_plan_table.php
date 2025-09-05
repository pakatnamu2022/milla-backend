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
    Schema::create('ap_accounting_account_plan', function (Blueprint $table) {
      $table->id();
      $table->string('cuenta')->unique();
      $table->string('descripcion');
      $table->foreignId('tipo_cta_contable_id')
        ->constrained('ap_commercial_masters');
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
    Schema::dropIfExists('ap_accounting_account_plan');
  }
};
