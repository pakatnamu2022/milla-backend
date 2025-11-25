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
    Schema::create('appointment_planning', function (Blueprint $table) {
      $table->id();
      $table->date('date_appointment');
      $table->time('time_appointment');
      $table->string('full_name_client');
      $table->string('email_client');
      $table->string('phone_client');
      $table->integer('created_by');
      $table->foreign('created_by')->references('id')->on('usr_users')->onDelete('cascade');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('appointment_planning');
  }
};
