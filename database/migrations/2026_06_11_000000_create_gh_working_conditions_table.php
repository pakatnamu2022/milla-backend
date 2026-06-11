<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gh_working_conditions', function (Blueprint $table) {
            $table->id();
            $table->integer('worker_id');
            $table->unsignedBigInteger('period_id');
            $table->decimal('amount', 12);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('worker_id')->references('id')->on('rrhh_persona');
            $table->foreign('period_id')->references('id')->on('gh_payroll_periods');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gh_working_conditions');
    }
};