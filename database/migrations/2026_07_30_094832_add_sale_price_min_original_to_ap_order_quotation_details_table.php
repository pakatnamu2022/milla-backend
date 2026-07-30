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
        Schema::table('ap_order_quotation_details', function (Blueprint $table) {
            $table->decimal('sale_price_min_original', 10, 2)->nullable()->after('unit_price')
                ->comment('Precio de venta mínimo registrado al momento de crear/actualizar el item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_order_quotation_details', function (Blueprint $table) {
            $table->dropColumn('sale_price_min_original');
        });
    }
};
