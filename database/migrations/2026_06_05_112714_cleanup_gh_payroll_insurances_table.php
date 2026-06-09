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
        Schema::table('gh_payroll_insurances', function (Blueprint $table) {
            // Agregar nuevos campos para información del contratante
            $table->string('contracting_name', 255)->nullable()->after('rate_with_tax');
            $table->string('num_doc_contracting', 20)->nullable()->after('contracting_name');

            // Eliminar campos que ya no se utilizan
            $table->dropColumn([
                'family_group_number',
                'relationship',
                'doc_type_affiliate',
                'gender',
                'paternal_surname',
                'maternal_surname',
                'first_name',
                'second_name',
                'entry_date',
                'birth_date',
                'condition',
                'program',
                'plan',
                'payment_frequency',
                'type',
                'rate_without_tax',
                'tax',
                'period_from',
                'period_until',
                'affiliation_continuity_date',
                'affiliation_from',
                'affiliation_until',
                'status',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gh_payroll_insurances', function (Blueprint $table) {
            // Restaurar campos eliminados
            $table->string('family_group_number', 50)->nullable();
            $table->string('relationship', 50);
            $table->string('doc_type_affiliate', 50)->nullable();
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
            $table->date('period_from')->nullable();
            $table->date('period_until')->nullable();
            $table->date('affiliation_continuity_date')->nullable();
            $table->date('affiliation_from')->nullable();
            $table->date('affiliation_until')->nullable();
            $table->tinyInteger('status')->default(1);

            // Eliminar nuevos campos
            $table->dropColumn(['contracting_name', 'num_doc_contracting']);
        });
    }
};
