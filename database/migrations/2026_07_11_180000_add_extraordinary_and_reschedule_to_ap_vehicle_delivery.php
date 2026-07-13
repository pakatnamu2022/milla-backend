<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('ap_vehicle_delivery', function (Blueprint $table) {
      $table->boolean('is_extraordinary')->default(false)->after('is_accounted');
      $table->boolean('extraordinary_approved')->nullable()->after('is_extraordinary');
      $table->timestamp('extraordinary_approved_at')->nullable()->after('extraordinary_approved');
      $table->unsignedBigInteger('extraordinary_sent_by')->nullable()->after('extraordinary_approved_at');
      $table->string('extraordinary_token', 64)->nullable()->unique()->after('extraordinary_sent_by');
      $table->unsignedBigInteger('rescheduled_by')->nullable()->after('extraordinary_token');
    });
  }

  public function down(): void
  {
    Schema::table('ap_vehicle_delivery', function (Blueprint $table) {
      $table->dropColumn([
        'is_extraordinary',
        'extraordinary_approved',
        'extraordinary_approved_at',
        'extraordinary_sent_by',
        'extraordinary_token',
        'rescheduled_by',
      ]);
    });
  }
};
