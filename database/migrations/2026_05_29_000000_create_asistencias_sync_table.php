<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('attendance_sync', function (Blueprint $table) {
      $table->id();
      $table->unsignedInteger('zkbio_transaction_id')->unique();
      $table->unsignedBigInteger('person_id')->nullable();
      $table->string('emp_code', 20);
      $table->string('full_name', 200);
      $table->date('date');
      $table->enum('mark_type', ['check_in', 'lunch_out', 'lunch_in', 'check_out']);
      $table->time('time');
      $table->string('area', 100)->nullable();
      $table->string('punch_state_original', 10);
      $table->timestamp('synced_at')->useCurrent();
      $table->timestamps();

      $table->foreign('person_id')->references('id')->on('rrhh_persona')->nullOnDelete();
      $table->index(['date', 'emp_code']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('attendance_sync');
  }
};
