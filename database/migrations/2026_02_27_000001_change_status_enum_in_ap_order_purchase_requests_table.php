<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('ap_order_purchase_requests', function (Blueprint $table) {
      $table->enum('status', ['pending', 'ordered', 'received', 'cancelled'])
        ->default('pending')
        ->change();
    });
  }

  public function down(): void
  {
    Schema::table('ap_order_purchase_requests', function (Blueprint $table) {
      $table->enum('status', ['pending', 'approved', 'rejected'])
        ->default('pending')
        ->change();
    });
  }
};