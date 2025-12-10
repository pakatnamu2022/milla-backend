<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   * *hotel_reservations**
   * ```
   * - id
   * - per_diem_request_id (foreignId -> per_diem_requests, cascadeOnDelete, unique)
   * - hotel_agreement_id (foreignId -> hotel_agreements, nullable, nullOnDelete)
   * - hotel_name (string)
   * - address (string)
   * - phone (string, nullable)
   * - checkin_date (date)
   * - checkout_date (date)
   * - nights_count (integer)
   * - total_cost (decimal 10,2)
   * - receipt_path (string, nullable)
   * - notes (text, nullable)
   * - attended (boolean, nullable)
   * - penalty (decimal 10,2, default 0)
   * - timestamps
   * ```
   */
  public function up(): void
  {
    Schema::create('gh_hotel_reservation', function (Blueprint $table) {
      $table->id();
      $table->foreignId('per_diem_request_id')->comment('Reference to the per diem request')->constrained('gh_per_diem_request')->cascadeOnDelete();
      $table->foreignId('hotel_agreement_id')->comment('Reference to the hotel agreement, if applicable')->nullable()->constrained('gh_hotel_agreement')->nullOnDelete();
      $table->string('hotel_name')->comment('Name of the hotel where the reservation is made');
      $table->string('address')->comment('Address of the hotel');
      $table->string('phone')->nullable()->comment('Contact phone number of the hotel');
      $table->date('checkin_date')->comment('Check-in date for the hotel reservation');
      $table->date('checkout_date')->comment('Check-out date for the hotel reservation');
      $table->integer('nights_count')->comment('Number of nights for the hotel stay');
      $table->decimal('total_cost', 10)->comment('Total cost of the hotel reservation');
      $table->string('receipt_path')->nullable()->comment('Path to the receipt or invoice for the hotel reservation');
      $table->text('notes')->nullable()->comment('Additional notes regarding the hotel reservation');
      $table->boolean('attended')->nullable()->comment('Indicates if the employee attended the reservation');
      $table->decimal('penalty', 10)->default(0)->comment('Penalty amount if applicable');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_hotel_reservation');
  }
};
