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
    Schema::create('phone_line', function (Blueprint $table) {
      $table->id();
      $table->foreignId('telephone_account_id')->constrained('telephone_account')->onDelete('cascade')->comment('Cuenta telefónica asociada');
      $table->foreignId('telephone_plan_id')->constrained('telephone_plan')->onDelete('cascade')->comment('Plan telefónico asociado');
      $table->string('line_number')->unique()->comment('Número de línea telefónica, ej: 915397187');
      $table->enum('status', ['active', 'inactive'])->default('active')->comment('Estado de la línea');
      $table->boolean('is_active')->default(true)->comment('Indica si la línea está activa');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('phone_line');
  }
};
