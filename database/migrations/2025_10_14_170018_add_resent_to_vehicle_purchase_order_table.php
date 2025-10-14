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
        Schema::table('ap_vehicle_purchase_order', function (Blueprint $table) {
            $table->boolean('resent')
                ->default(false)
                ->after('migration_status')
                ->comment('Indica si la OC anulada ya fue reenviada (true=ya reenviada, false=no reenviada)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_vehicle_purchase_order', function (Blueprint $table) {
            $table->dropColumn('resent');
        });
    }
};
