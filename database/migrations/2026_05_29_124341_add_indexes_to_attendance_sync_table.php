<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('attendance_sync', function (Blueprint $table) {
      $table->unique('zkbio_transaction_id');
      $table->index(['date', 'emp_code']);
    });
  }

  public function down(): void
  {
    Schema::table('attendance_sync', function (Blueprint $table) {
      $table->dropUnique(['zkbio_transaction_id']);
      $table->dropIndex(['date', 'emp_code']);
    });
  }
};
