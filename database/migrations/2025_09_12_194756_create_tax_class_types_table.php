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
    Schema::create('tax_class_types', function (Blueprint $table) {
      $table->id();
      $table->string('dyn_code');
      $table->string('description');
      $table->enum('type', ['CLIENTE', 'PROVEEDOR']);
      $table->boolean('status')->default(true);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tax_class_types');
  }
};
