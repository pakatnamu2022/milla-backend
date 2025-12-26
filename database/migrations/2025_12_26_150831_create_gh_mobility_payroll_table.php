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
    Schema::create('gh_mobility_payroll', function (Blueprint $table) {
      $table->id();
      $table->integer('worker_id')->comment('ID del trabajador');
      $table->string('num_doc')->comment('Número de documento del trabajador');
      $table->string('company_name')->comment('Nombre de la empresa');
      $table->string('address')->nullable()->comment('Dirección');
      $table->string('serie', 10)->comment('Serie del documento');
      $table->string('correlative', 20)->comment('Número correlativo');
      $table->string('period', 20)->comment('Período (mes/año)');
      $table->integer('sede_id')->nullable()->comment('ID de la sede');
      $table->timestamps();
      $table->softDeletes();

      // Foreign keys
      $table->foreign('worker_id')->references('id')->on('rrhh_persona')->onDelete('cascade');
      $table->foreign('sede_id')->references('id')->on('config_sede')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_mobility_payroll');
  }
};
