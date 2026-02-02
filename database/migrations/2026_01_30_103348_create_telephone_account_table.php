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
    Schema::create('telephone_account', function (Blueprint $table) {
      $table->id();
      $table->foreignId('company_id')->constrained('companies')->onDelete('cascade')->comment('Empresa asociada');
      $table->string('account_number')->unique()->comment('Número de cuenta, ej: 8.17442986.00.00.100000');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('telephone_account');
  }
};
