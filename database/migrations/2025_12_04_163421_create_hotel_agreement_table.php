<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   * *hotel_agreements**
   * ```
   * - id
   * - city (string, indexed)
   * - name (string)
   * - corporate_rate (decimal 8,2)
   * - features (text, nullable)
   * - includes_breakfast (boolean, default false)
   * - includes_parking (boolean, default false)
   * - contact (string)
   * - address (string)
   * - website (string, nullable)
   * - active (boolean, default true)
   * - timestamps
   * ```
   */
  public function up(): void
  {
    Schema::create('gh_hotel_agreement', function (Blueprint $table) {
      $table->id();
      $table->string('city')->index()->comment('City where the hotel agreement is applicable');
      $table->text('name')->comment('Name of the hotel');
      $table->decimal('corporate_rate')->comment('Corporate rate agreed upon with the hotel');
      $table->text('features')->nullable()->comment('Features and amenities included in the agreement, by comma separation');
      $table->boolean('includes_breakfast')->default(false)->comment('Indicates if breakfast is included in the corporate rate');
      $table->boolean('includes_lunch')->default(false)->comment('Indicates if lunch is included in the agreement');
      $table->boolean('includes_dinner')->default(false)->comment('Indicates if dinner is included in the agreement');
      $table->boolean('includes_parking')->default(false)->comment('Indicates if parking is included in the corporate rate');
      $table->string('email')->nullable()->comment('Contact email for the hotel agreement');
      $table->string('phone')->nullable()->comment('Contact phone number for the hotel agreement');
      $table->string('address')->comment('Address of the hotel');
      $table->string('website')->nullable()->comment('Website of the hotel');
      $table->boolean('active')->default(true)->comment('Indicates if the hotel agreement is active');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_hotel_agreement');
  }
};
