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
    Schema::create('worker_signature', function (Blueprint $table) {
      $table->id();
      $table->string('signature_url', 500);
      $table->integer('worker_id');
      $table->foreign('worker_id')->references('id')->on('rrhh_persona')->onDelete('cascade');
      $table->foreignId('company_id')->default(3)->constrained('companies')->onDelete('cascade'); // por defecto EMPRESA AUTOMOTORES
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('workers_signatures');
  }
};
