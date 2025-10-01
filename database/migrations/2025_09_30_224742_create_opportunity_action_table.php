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
    Schema::create('opportunity_action', function (Blueprint $table) {
      $table->id();

      $table->foreignId('opportunity_id')->constrained('ap_opportunity');
      $table->foreignId('action_type_id')->constrained('ap_commercial_masters');
      $table->foreignId('action_contact_type_id')->constrained('ap_commercial_masters');

      $table->dateTime('datetime');
      $table->text('description')->nullable();
      $table->boolean('result')->default(0);

      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('opportunity_action');
  }
};
