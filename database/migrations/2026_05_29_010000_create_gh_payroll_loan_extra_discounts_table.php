<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gh_payroll_loan_extra_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->string('concept_type', 100);
            $table->decimal('amount', 12, 2)->default(0);
            $table->integer('month_number')->nullable();
            $table->boolean('applied')->default(false);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('loan_id')->references('id')->on('gh_payroll_loans')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gh_payroll_loan_extra_discounts');
    }
};