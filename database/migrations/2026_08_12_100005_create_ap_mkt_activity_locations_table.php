<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('ap_mkt_activity_locations', function (Blueprint $table) {
      $table->id();
      $table->integer('sede_id')->nullable();
      $table->string('location_name', 150)->nullable();
      $table->decimal('amount', 14, 2)->default(0);
      $table->text('notes')->nullable();
      $table->timestamps();

      $table->foreignId('activity_id')->constrained('ap_mkt_activities');
      $table->foreignId('currency_id')->constrained('type_currency');
      $table->foreign('sede_id')->references('id')->on('config_sede')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_mkt_activity_locations');
  }
};
