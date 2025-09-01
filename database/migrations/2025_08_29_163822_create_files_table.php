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
    Schema::create('gp_digital_files', function (Blueprint $table) {
      $table->id();
      $table->text('name');
      $table->string('model');
      $table->integer('id_model');
      $table->text('url');
      $table->string('mimeType');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gp_digital_files');
  }
};
