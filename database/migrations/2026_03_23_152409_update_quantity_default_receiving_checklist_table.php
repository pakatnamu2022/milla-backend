<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ap_receiving_checklist', function (Blueprint $table) {
            $table->integer('quantity')->default(1)->change();
        });

        // Actualizar registros existentes con quantity = 0
        DB::table('ap_receiving_checklist')->where('quantity', 0)->update(['quantity' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_receiving_checklist', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->change();
        });
    }
};
