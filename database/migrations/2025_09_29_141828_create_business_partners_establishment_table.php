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
    Schema::create('business_partners_establishment', function (Blueprint $table) {
      $table->id();
      $table->string('code');
      $table->string('type');
      $table->string('activity_economic');
      $table->string('address');
      $table->string('full_address');
      $table->string('ubigeo');
      $table->foreignId('business_partner_id')
        ->constrained('business_partners')->onDelete('cascade');
      $table->timestamps();
      $table->SoftDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('business_partners_establishment');
  }
};
