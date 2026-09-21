<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gh_payroll_loans', function (Blueprint $table) {
            // Concepto del sistema anterior (PRESTAMO, ADELANTO, TELEFONO...). NULL en los creados aquí.
            $table->string('concept', 50)->nullable()->after('legacy_id');
        });
    }

    public function down(): void
    {
        Schema::table('gh_payroll_loans', function (Blueprint $table) {
            $table->dropColumn('concept');
        });
    }
};
