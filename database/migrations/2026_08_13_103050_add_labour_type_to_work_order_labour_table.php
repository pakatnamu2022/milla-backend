<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_order_labour', function (Blueprint $table) {
            $table->string('labour_type', 50)->default('labor')->after('description');
            $table->index('labour_type');
        });

        // Migrar datos existentes: detectar MATERIALES y variaciones comunes
        DB::statement("
            UPDATE work_order_labour
            SET labour_type = 'material'
            WHERE UPPER(TRIM(description)) IN (
                'MATERIALES',
                'MATERILAES',
                'MATERALES',
                'MATERIIALES',
                'MTERIALES'
            )
            OR UPPER(TRIM(description)) LIKE '%MATERIAL%'
        ");

        // Migrar datos existentes: detectar DEDUCIBLE
        DB::statement("
            UPDATE work_order_labour
            SET labour_type = 'deductible'
            WHERE is_deductible = 1
            OR UPPER(TRIM(description)) LIKE '%DEDUCIBLE%'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_labour', function (Blueprint $table) {
            $table->dropIndex(['labour_type']);
            $table->dropColumn('labour_type');
        });
    }
};
