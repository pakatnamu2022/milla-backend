<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use function Symfony\Component\Translation\t;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('exchange_rate', function (Blueprint $table) {
      $table->id();
      $table->foreignId('from_currency_id')->constrained('type_currency');
      $table->foreignId('to_currency_id')->constrained('type_currency');
      $table->enum('type', ['VENTA', 'NEGOCIADOR']);
      $table->date('date');
      $table->decimal('rate', 15, 7);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('exchange_rate');
  }
};
