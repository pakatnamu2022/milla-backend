<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gh_payroll_family_allowance', function (Blueprint $table) {
            $table->id();
            $table->integer('worker_id');
            $table->unsignedBigInteger('period_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('applies')->default(true)->comment('Indica si el beneficio aplica para este periodo');
            $table->string('num_doc', 20)->nullable()->comment('Número de documento del trabajador en ese momento');
            $table->string('full_name', 255)->nullable()->comment('Nombre completo del trabajador en ese momento');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('worker_id')->references('id')->on('rrhh_persona');
            $table->foreign('period_id')->references('id')->on('gh_payroll_periods');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gh_payroll_family_allowance');
    }
};
