<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('gh_payroll_loans', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('concept_id')->nullable();
      $table->integer('worker_id');
      $table->date('delivery_date')->nullable();
      $table->string('reason', 255)->nullable();
      $table->date('payment_start')->nullable();
      $table->decimal('loan_amount', 12)->default(0);
      $table->integer('installments_count')->default(1);
      $table->decimal('installment_amount', 12)->default(0);
      $table->tinyInteger('status')->default(1);
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('concept_id')->references('id')->on('general_masters');
      $table->foreign('worker_id')->references('id')->on('rrhh_persona');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('gh_payroll_loans');
  }
};
