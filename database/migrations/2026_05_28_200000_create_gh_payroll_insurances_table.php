<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gh_payroll_insurances', function (Blueprint $table) {
            $table->id();
            $table->integer('worker_id');
            $table->unsignedBigInteger('period_id');
            $table->unsignedBigInteger('business_partner_id');
            $table->string('family_group_number', 50)->nullable();
            $table->string('relationship', 50);
            $table->string('doc_type_affiliate', 50)->nullable();
            $table->string('doc_number_affiliate', 20)->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('paternal_surname', 100)->nullable();
            $table->string('maternal_surname', 100)->nullable();
            $table->string('first_name', 100)->nullable();
            $table->string('second_name', 100)->nullable();
            $table->date('entry_date')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('condition', 100)->nullable();
            $table->string('program', 150)->nullable();
            $table->string('plan', 150)->nullable();
            $table->string('payment_frequency', 50)->nullable();
            $table->string('type', 100)->nullable();
            $table->decimal('rate_without_tax', 12)->default(0);
            $table->decimal('tax', 12)->default(0);
            $table->decimal('rate_with_tax', 12)->default(0);
            $table->date('period_from')->nullable();
            $table->date('period_until')->nullable();
            $table->date('affiliation_continuity_date')->nullable();
            $table->date('affiliation_from')->nullable();
            $table->date('affiliation_until')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('worker_id')->references('id')->on('rrhh_persona');
            $table->foreign('period_id')->references('id')->on('gh_payroll_periods');
            $table->foreign('business_partner_id')->references('id')->on('business_partners');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gh_payroll_insurances');
    }
};