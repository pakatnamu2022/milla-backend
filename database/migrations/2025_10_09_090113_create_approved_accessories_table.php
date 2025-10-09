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
    Schema::create('approved_accessories', function (Blueprint $table) {
      $table->id();
      $table->string('code', 20);
      $table->enum('type', ['SERVICIO', 'REPUESTO']);
      $table->string('description');
      $table->decimal('price', 12, 4);
      $table->boolean('status')->default(true);
      $table->foreignId('type_currency_id')
        ->constrained('type_currency')->onDelete('cascade');
      $table->foreignId('body_type_id')
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('approved_accessories');
  }
};
