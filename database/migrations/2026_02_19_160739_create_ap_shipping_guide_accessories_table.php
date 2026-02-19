<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('ap_shipping_guide_accessories', function (Blueprint $table) {
      $table->id();
      $table->foreignId('shipping_guide_id')->constrained('shipping_guides')->cascadeOnDelete();
      $table->string('description', 255);
      $table->decimal('quantity', 10, 2)->default(1);
      $table->foreignId('unit_measurement_id')->nullable()->constrained('unit_measurement')->nullOnDelete();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_shipping_guide_accessories');
  }
};
