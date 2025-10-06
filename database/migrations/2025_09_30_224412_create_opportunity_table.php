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
    Schema::create('ap_opportunity', function (Blueprint $table) {
      $table->id();

      $table->integer('worker_id');
      $table->foreign('worker_id')->references('id')->on('rrhh_persona');

      $table->foreignId('client_id')->constrained('business_partners');
      $table->foreignId('family_id')->constrained('ap_families');
      $table->foreignId('opportunity_type_id')->constrained('ap_commercial_masters');
      $table->foreignId('client_status_id')->constrained('ap_commercial_masters');
      $table->foreignId('opportunity_status_id')->constrained('ap_commercial_masters');

      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_opportunity');
  }
};
